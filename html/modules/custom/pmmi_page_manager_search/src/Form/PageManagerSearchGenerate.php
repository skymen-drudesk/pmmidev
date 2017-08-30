<?php

namespace Drupal\pmmi_page_manager_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PageManagerSearchGenerate.
 *
 * @package Drupal\product_importer\Form
 */
class PageManagerSearchGenerate extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_search_generate';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start generate'),
      '#weight' => 100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operation = [];

    $all_pages = \Drupal::entityQuery('page_variant')
      ->execute();

    foreach ($all_pages as $key => $value) {
      $operation[] = [
        '\Drupal\pmmi_page_manager_search\Controller\PageManagerSearchGenerateBatch::bulkGenerate',
        [$value],
      ];
    }

    $batch = [
      'title' => t('Bulk Generate...'),
      'operations' => $operation,
      'finished' => '\Drupal\pmmi_page_manager_search\Controller\PageManagerSearchGenerateBatch::bulkGenerateFinishedCallback',
    ];

    batch_set($batch);
  }
}
