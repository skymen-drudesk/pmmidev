<?php

/**
 * @file
 * Contains \Drupal\pmmi_company_contact\Entity\Form\PMMICompanyContactEntityForm.
 */

namespace Drupal\pmmi_company_contact\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for Company contact edit forms.
 *
 * @ingroup pmmi_company_contact
 */
class PMMICompanyContactEntityForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\pmmi_company_contact\Entity\PMMICompanyContactEntity */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->langcode->value,
      '#languages' => Language::STATE_ALL,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    // Build the entity object from the submitted values.
    $entity = parent::submit($form, $form_state);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Company contact.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Company contact.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.pmmi_company_contact.edit_form', ['pmmi_company_contact' => $entity->id()]);
  }

}
