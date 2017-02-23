<?php

namespace Drupal\pmmi_sso\Entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @internal
 */
class PMMISSOTokenSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'pmmi_sso_token_settings';
  }

  /**
   * Defines the settings form for Access Token entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['expiration'] = [
      '#type' => 'number',
      '#title' => $this->t('Expiration time'),
      '#description' => $this->t('The default value, in seconds, to be used as expiration time when creating new tokens.'),
      '#default_value' => $this->config('pmmi_sso.settings')->get('expiration'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->configFactory()->getEditable('pmmi_sso.settings');
    $settings->set('expiration', $form_state->getValue('expiration'));
    $settings->save();
  }

}
