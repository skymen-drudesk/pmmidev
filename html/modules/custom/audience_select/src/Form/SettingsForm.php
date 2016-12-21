<?php

namespace Drupal\audience_select\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\audience_select\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'audience_select.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('audience_select.settings');
    $form['gateway_page_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gateway Page URL'),
      '#description' => t('Enter the internal path to the gateway page.
        Do not include the leading slash.
      '),
      '#required' => TRUE,
      '#default_value' => $config->get('gateway_page_url'),
    ];

    $form['audiences'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Audiences'),
      '#description' => t('Enter each audience on its own line as: 
        <pre>key|Gateway Value|opt: Block Value</pre>
        If no block value is specified, the gateway value will be used.'
      ),
      '#default_value' => $config->get('audiences'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $gateway_page_url = $form_state->getValue('gateway_page_url');

    if (substr($gateway_page_url, 0, 1) === "/") {
      $form_state->setErrorByName(
        'gateway_page_url',
        $this->t('URL cannot begin with <pre>/</pre>')
      );
    }

    $audiences = $form_state->getValue('audiences');

    if (strlen($audiences) < 3
      && strpos($audiences, '|') === FALSE
    ) {
      $form_state->setErrorByName(
        'audiences',
        $this->t('Enter each audience on its own line as: 
        <pre>key|Select Value|opt: Switcher Value</pre>
        If no switcher value is specified, the select value will be used.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('audience_select.settings')
      ->set(
        'gateway_page_url',
        $form_state->getValue('gateway_page_url')
      )
      ->set(
        'audiences',
        $form_state->getValue('audiences')
      )
      ->set(
        'template',
        $form_state->getValue('template')
      )
      ->save();
  }

}
