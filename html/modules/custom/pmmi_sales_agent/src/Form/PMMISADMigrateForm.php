<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate_tools\MigrateExecutable;

/**
 * Provide a form to upload .xslx file in order to import new companies.
 */
class PMMISADMigrateForm extends FormBase implements MigrateMessageInterface {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_sad_migrate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Before import:'),
      '#description' => $this->t('Your import file should include only
        companies, which you want to import. If some item has been failed, you
        can find a reason of failing on the "Messages" tab. After that, you can
        fix it and re-import (only failed items will be imported at a second
        time).'
      ),
    ];

    $form['company_migrate_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload import file'),
      '#maxlength' => 40,
    ];

    // @todo: at the moment we allow import process only. The next features
    // Rollback/Stop/Reset are not available. Do we need them?
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $validators = ['file_validate_extensions' => ['xlsx']];

    // Check for a new uploaded logo.
    $file = file_save_upload('company_migrate_upload', $validators, FALSE, 0);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue('company_migrate_upload', $file);
      }
      else {
        // File upload failed.
        $form_state->setErrorByName('company_migrate_upload', $this->t('The import file could not be uploaded.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    // If the user uploaded a new company import file, save it to a permanent
    // location.
    if (!empty($values['company_migrate_upload'])) {
      $uri = file_unmanaged_copy($values['company_migrate_upload']->getFileUri());

      // Continue only if necessary migration (company_migrate) is available.
      $migrationPluginManager = \Drupal::service('plugin.manager.config_entity_migration');
      $migrations = $migrationPluginManager->createInstances(['company_migrate']);
      if (!empty($migrations['company_migrate'])) {
        // Replace import file to the new one and import new companies from it.
        $source = $migrations['company_migrate']->get('source');
        $source['file'] = \Drupal::service('file_system')->realpath($uri);
        $migrations['company_migrate']->set('source', $source);

        // Prepare this migration to run as an update. Update failed items only.
        // @see Drupal\migrate\Plugin\migrate\id_map\Sql::prepareUpdate.
        $idMap = $migrations['company_migrate']->getIdMap();
        $idMap->getDatabase()->update($idMap->mapTableName())
          ->fields(['source_row_status' => 1])
          ->condition('source_row_status', 3)
          ->execute();

        // This is a fake batch as we will always have only one operation. We
        // use default process of migration module and it limits us in some
        // actions.
        // @todo: fix it as soon as possible!
        $batch = [
          'operations' => [[[$this, 'batchProcess'], [$migrations['company_migrate']]]],
          'title' => t('Import processing'),
          'init_message' => t('Starting import process'),
          'progress_message' => t('Processing import of the companies.'),
          'error_message' => t('An error occurred. Some or all of the import processing has failed.'),
          'finished' => [$this, 'batchFinished'],
        ];

        batch_set($batch);
      }
    }
  }

  /**
   * Batch 'operation' callback.
   */
  public function batchProcess($migration, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['offset'] = 0;
      $total = $source_plugin = $migration->getSourcePlugin()->count();
      $context['sandbox']['max'] = $total;
    }

    $executable = new MigrateExecutable($migration, $this);
    call_user_func(array($executable, 'import'));

    $context['sandbox']['results'] = 'OK';
    $context['sandbox']['progress'] = $context['sandbox']['max'];
    $context['sandbox']['offset'] = $context['sandbox']['max'];
    $context['message'] = 'Processed ' . $migration->id();

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Batch 'finished' callback
   */
  public function batchFinished($success, $results, $operations) {
   if (!$success) {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE)
      ]);
      drupal_set_message($message, 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    drupal_set_message($message, ($type == 'error' ? 'error' : 'notice'));
  }
}
