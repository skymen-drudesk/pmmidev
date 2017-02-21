<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PMMISalesAgentMailMassForm.
 *
 * @package Drupal\pmmi_sales_agent\Form
 */
class PMMISalesAgentMailMassForm extends ConfigFormBase {

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
    return 'pmmi_sales_agent_mail_mass_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('pmmi_sales_agent.mail_settings');

    // @todo: add info about last run.
    // @todo: add form to set reminder period.

    $form['actions']['submit']['#value'] = $this->t('Send a mass email');
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
    // @todo: save some configs.
    $url = \Drupal\Core\Url::fromRoute('pmmi_sales_agent.mass_email_confirm');
    $form_state->setRedirectUrl($url);
  }
}
