<?php

namespace Drupal\pmmi_sales_agent\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit form for sales agent downloads quota.
 */
class SADDownloadsQuotaEditForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\pmmi_sales_agent\SADDownloadsQuotaInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('User'),
      '#required' => TRUE,
      '#selection_settings' => array('role_target_id' => array('pmmi_member')),
    ];
    $form['quota'] = [
      '#type' => 'number',
      '#title' => $this->t('Records per year'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('path');

    // User-level quota already added!
    // @todo: add validation!
//    $path = $form_state->getValue('path');
//
//    if (!\Drupal::service('path.validator')->isValid($path)) {
//      $form_state->setErrorByName('path', t("The path '@path' is either invalid or you do not have access to it.", ['@path' => $path]));
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->t('Downloads quota has been changed for @user.'));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }
}
