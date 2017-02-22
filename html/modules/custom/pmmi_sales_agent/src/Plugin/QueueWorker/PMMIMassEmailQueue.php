<?php

/**
 * @file
 * Contains \Drupal\pmmi_sales_agent\Plugin\QueueWorker\PMMIMassEmailQueue.
 */

namespace Drupal\pmmi_sales_agent\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\Entity\Node;

/**
 * Processes companies to receive remind message.
 *
 * @QueueWorker(
 *   id = "pmmm_mass_email_remind_queue",
 *   title = @Translation("PMMI: mass email remind"),
 *   cron = {"time" = 60}
 * )
 */
class PMMIMassEmailQueue extends QueueWorkerBase {
  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $node = Node::load($data);
    if ($node && $node->hasField('field_primary_contact_email')) {
      $to = $node->get('field_primary_contact_email')->getValue();

      // Check if email is valid.
      if (!empty($to[0]['value']) && \Drupal::service('email.validator')->isValid($to[0]['value'])) {
        // Compose and send remind email.
        $mailManager = \Drupal::service('plugin.manager.mail');
        $mail_settings = \Drupal::config('pmmi_sales_agent.mail_settings');
        $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

        $params = [
          'subject' => $mail_settings->get('ss_update_reminder.subject'),
          'body' => $mail_settings->get('ss_update_reminder.body'),
          'from' => $mail_settings->get('mail_notification_address'),
        ];

        $mailManager->mail('pmmi_sales_agent', 'pmmi_sales_agent_mass_remind', $to[0]['value'], $current_langcode, $params);

        // Update mass mail status.
        if ($mm_statue = $node->get('field_mass_mail_status')->getValue()) {
          $node->set('field_mass_mail_status', ++$mm_statue[0]['value']);
          $node->save();
        }
      }
    }
  }
}
