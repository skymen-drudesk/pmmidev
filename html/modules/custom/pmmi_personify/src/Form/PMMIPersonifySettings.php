<?php

namespace Drupal\pmmi_personify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMIPersonifySettings.
 *
 * @package Drupal\pmmi_personify\Form
 */
class PMMIPersonifySettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pmmi_personify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_personify_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pmmi_personify.settings');
    $form['personify'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personify Data'),
    ];
    $form['personify']['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint URL'),
      '#maxlength' => 1024,
      '#size' => 80,
      '#required' => TRUE,
      '#default_value' => $config->get('endpoint'),
    ];
    $form['personify']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify username'),
      '#maxlength' => 255,
      '#size' => 80,
      '#required' => TRUE,
      '#default_value' => $config->get('username'),
    ];
    $form['personify']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personify password'),
      '#maxlength' => 255,
      '#size' => 80,
      '#required' => TRUE,
      '#default_value' => $config->get('password'),
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

    $this->config('pmmi_personify.settings')
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();
  }

}
