<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form for sending mass email.
 */
class PMMISalesAgentMailMassConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_sales_agent_mail_mass_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to send a mass email?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('pmmi_sales_agent.mass_email');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Only do this if you are sure!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Continue');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = [];

    $nids = \Drupal::entityQuery('node')
      // @todo: any condition?
      ->condition('type', 'company')
      ->execute();

    if ($nids) {
      // The sales agent mail settings.
      $mail_settings = \Drupal::config('pmmi_sales_agent.mail_settings');

      // Process 10 items per operation.
      foreach (array_chunk($nids, 10) as $chunk) {
        $operations[] = [
          [__CLASS__, 'process'],
          [$chunk, $mail_settings->get('ss_update.subject'), $mail_settings->get('ss_update.body'), $mail_settings->get('mail_notification_address')]
        ];
        // @todo: remove break (this is for testing only)!
        break;
      }
    }

    $batch_definition = [
      'operations' => $operations,
      'finished' => [__CLASS__, 'finish'],
    ];

    // Schedule the batch.
    batch_set($batch_definition);
  }

  /**
   * Processes an email batch operation.
   *
   * @param array $nids
   *   The company IDs.
   * @param string $subject
   *   The mail subject.
   * @param string $body
   *   The mail body.
   * @param string $from
   *   The email address to be used as the 'from' address.
   * @param array|\ArrayAccess $context
   *   The context of the current batch, as defined in the @link batch Batch
   *   operations @endlink documentation.
   */
  public static function process($nids, $subject, $body, $from, &$context) {
    $entity_manager = \Drupal::entityManager();
    $mailManager = \Drupal::service('plugin.manager.mail');

    $nodes = $entity_manager->getStorage('node')->loadMultiple($nids);
    foreach ($nodes as $node) {
      $to = $node->get('field_primary_contact_email')->getValue();
      if (!empty($to[0]['value'])) {
        // Compose and send an email.
        $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $params = ['subject' => $subject, 'body' => $body, 'from' => $params['from']];

        $result = $mailManager->mail('pmmi_sales_agent', 'pmmi_sales_agent_mass', $to[0]['value'], $current_langcode, $params);

        // @todo: track results.
        if ($result['result'] !== true) {

        }
        else {

        }
      }
    }
  }

  /**
   * Finishes a batch.
   */
  public static function finish($success, $results, $operations) {}
}
