<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide reporting settings form.
 */
class PMMISADReportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pmmi_sales_agent.reporting_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_sad_report_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('pmmi_sales_agent.reporting_settings');

    $form['download_favourites'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Download favourites'),
    ];
    $form['download_favourites']['records_per_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Records per year'),
      '#default_value' => $config->get('records_per_year'),
      '#description' => $this->t('Default number of records per year which are available for downloading.'),
      '#required' => TRUE,
    ];
    $form['download_favourites']['exceeded_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exceeded limit message'),
      '#default_value' => $config->get('exceeded_message'),
      '#description' => $this->t('A message which is appear if download limit has been exceeded.'),
      '#required' => TRUE,
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
    $this->configFactory()->getEditable('pmmi_sales_agent.reporting_settings')
      ->set('records_per_year', $form_state->getValue('records_per_year'))
      ->set('exceeded_message', $form_state->getValue('exceeded_message'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
