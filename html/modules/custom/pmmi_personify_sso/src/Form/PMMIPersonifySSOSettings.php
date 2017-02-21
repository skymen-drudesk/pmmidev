<?php

namespace Drupal\pmmi_personify_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMIPersonifySSOSettings.
 *
 * @package Drupal\pmmi_personify_sso\Form
 */
class PMMIPersonifySSOSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pmmi_personify_sso.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_personify_sso_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pmmi_personify_sso.settings');
    $form['login_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login URI'),
      '#maxlength' => 128,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('login_uri'),
    ];
    $form['service_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service URI'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('service_uri'),
    ];
    $form['vi'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor Identifier'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('vi'),
    ];
    $form['vu'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor username'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('vu'),
    ];
    $form['vp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor password (HEX)'),
      '#maxlength' => 32,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('vp'),
    ];
    $form['vib'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vendor initilization block (HEX)'),
      '#maxlength' => 32,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('vib'),
    ];
    return parent::buildForm($form, $form_state);
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
    parent::submitForm($form, $form_state);

    $this->config('pmmi_personify_sso.settings')
      ->set('login_uri', $form_state->getValue('login_uri'))
      ->set('service_uri', $form_state->getValue('service_uri'))
      ->set('vi', $form_state->getValue('vi'))
      ->set('vu', $form_state->getValue('vu'))
      ->set('vp', $form_state->getValue('vp'))
      ->set('vib', $form_state->getValue('vib'))
      ->save();
  }

}
