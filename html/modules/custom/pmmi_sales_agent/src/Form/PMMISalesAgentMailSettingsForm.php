<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMISalesAgentMailSettingsForm.
 *
 * @package Drupal\pmmi_sales_agent\Form
 */
class PMMISalesAgentMailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pmmi_sales_agent.mail_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pmmi_sales_agent_mail_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('pmmi_sales_agent.mail_settings');
    $site_config = $this->config('system.site');

    // Default notifications address.
    $form['mail_notification_address'] = array(
      '#type' => 'email',
      '#title' => $this->t('Notification email address'),
      '#default_value' => $config->get('mail_notification_address'),
      '#description' => $this->t("The email address to be used for all notifications listed below. Leave empty to use the default system email address <em>(%site-email).</em>", array('%site-email' => $site_config->get('mail'))),
      '#maxlength' => 180,
    );

    $form['email_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
    ];

    // Self-service update your listing.
    $form['ss_update'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Mass email'),
      '#group' => 'email_settings',
    ];
    $form['ss_update']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('ss_update.subject'),
      '#required' => TRUE,
    ];
    $form['ss_update']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('ss_update.body'),
      '#required' => TRUE,
    ];

    // Self-service update your listing (reminder).
    $form['ss_update_reminder'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Mass email (reminder)'),
      '#group' => 'email_settings',
    ];
    $form['ss_update_reminder']['subject_reminder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('ss_update_reminder.subject'),
      '#required' => TRUE,
    ];
    $form['ss_update_reminder']['body_reminder'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('ss_update_reminder.body'),
      '#required' => TRUE,
    ];

    // Submit a listing.
    // @todo

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
    $this->configFactory()->getEditable('pmmi_sales_agent.mail_settings')
      ->set('ss_update.subject', $form_state->getValue('subject'))
      ->set('ss_update.body', $form_state->getValue('body'))
      ->set('ss_update_reminder.subject', $form_state->getValue('subject_reminder'))
      ->set('ss_update_reminder.body', $form_state->getValue('body_reminder'))
      ->set('mail_notification_address', $form_state->getValue('mail_notification_address'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
