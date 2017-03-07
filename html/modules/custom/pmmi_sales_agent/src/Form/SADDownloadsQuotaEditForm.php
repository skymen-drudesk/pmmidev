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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('User'),
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
      '#default_value' => $this->entity->getUser(),
    ];
    $form['quota'] = [
      '#type' => 'number',
      '#title' => $this->t('Records per year'),
      '#required' => TRUE,
      '#min' => 1,
      '#default_value' => $this->entity->getQuota(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = \Drupal::entityTypeManager()
      ->getStorage('sad_downloads_quota')
      ->load($form_state->getValue('id'));

    if ($entity) {
      $form_state->setErrorByName('id', t('The downloads quota already added to user @username', [
        '@username' => $entity->getUser()->getUserName(),
      ]));
    }
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
