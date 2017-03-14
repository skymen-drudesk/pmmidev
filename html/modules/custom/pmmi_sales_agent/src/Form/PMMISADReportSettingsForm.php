<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\block_content\Entity\BlockContent;

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

    $form['download_favorites'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Download favorites'),
    ];
    $form['download_favorites']['records_per_year'] = [
      '#type' => 'number',
      '#title' => $this->t('Records per year'),
      '#default_value' => $config->get('records_per_year'),
      '#description' => $this->t('Default number of records per year an user can download..'),
      '#required' => TRUE,
    ];
    $form['download_favorites']['exceeded_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exceeded limit message'),
      '#default_value' => $config->get('exceeded_message'),
      '#description' => $this->t('A message which is appear if download limit has been exceeded.'),
      '#required' => TRUE,
    ];
    $form['download_favorites']['img_header'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Image header'),
      '#description' => $this->t('Select an image header for the favorites progress bar page.'),
      '#target_type' => 'block_content',
      '#selection_handler' => 'default:block_content',
      '#selection_settings' => [
        'target_bundles' => ['image_header'],
      ],
    ];

    $bid = $config->get('img_header');
    if ($bid) {
      $block = BlockContent::load($bid);
      $form['download_favorites']['img_header']['#default_value'] = $block;
    }

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
      ->set('img_header', $form_state->getValue('img_header'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
