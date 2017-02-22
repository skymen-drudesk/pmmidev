<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Component\Datetime\DateTimePlus;
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

    $mm_config = \Drupal::service('config.factory')
      ->getEditable('pmmi_sales_agent.mail_mass_settings');

    // Save info about last run.
    $mm_config->set('last_run', time())->save();
    // Get all companies, which should receive mass email.
    $nids = $this->massMailGetContacts($mm_config->get('filter'));

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

    // Check if the results should be initialized.
    if (!isset($context['results']['processed'])) {
      // Initialize the results with data which is shared among the batch runs.
      $context['results']['all'] = 0;
      $context['results']['processed'] = 0;
    }

    $nodes = $entity_manager->getStorage('node')->loadMultiple($nids);
    foreach ($nodes as $node) {
      $context['results']['all']++;
      $to = $node->get('field_primary_contact_email')->getValue();
      if (!empty($to[0]['value'])) {
        // Compose and send an email.
        $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $params = ['subject' => $subject, 'body' => $body, 'from' => $params['from']];
        $result = $mailManager->mail('pmmi_sales_agent', 'pmmi_sales_agent_mass', $to[0]['value'], $current_langcode, $params);

        //
        $node->set('field_mass_mail_status', 1);
        $node->save();

        // @todo: do we need info about items which weren't processed?
        if ($result['result'] == true) {
          $context['results']['processed']++;
        }
      }
    }
  }

  /**
   * Finishes a batch.
   */
  public static function finish($success, $results, $operations) {
    // Check if the batch job was successful.
    if ($success) {
      // Display the number of items which were processed.
      drupal_set_message(t('Processed @processed companies from @companies.', ['@processed' => $results['processed'], '@companies' => $results['all']]));
    }
    else {
      // Notify user about batch job failure.
      drupal_set_message(t('An error occurred while trying send mass email. Check the logs for details.'), 'error');
    }
  }

  /**
   * Helper function to get all companies, which will receive remind mass email.
   */
  protected function massMailGetContacts($filter) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', PMMI_SALES_AGENT_CONTENT);

    if ($filter != 'all') {
      $date = DateTimePlus::createFromTimestamp(strtotime($filter))->format(DATETIME_DATETIME_STORAGE_FORMAT);
      $group = $query->orConditionGroup()
        ->condition('field_last_updated_on.value', $date, '<=')
        ->condition('field_last_updated_on', NULL, 'IS NULL');

      $query->condition($group);
    }

    return $query->execute();
  }
}
