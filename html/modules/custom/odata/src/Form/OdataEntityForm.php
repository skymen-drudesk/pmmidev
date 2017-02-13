<?php

namespace Drupal\odata\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OdataEntityForm.
 *
 * @package Drupal\odata\Form
 */
class OdataEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $odata_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $odata_entity->label(),
      '#description' => $this->t("Label for the Odata entity."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $odata_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\odata\Entity\OdataEntity::load',
      ],
      '#disabled' => !$odata_entity->isNew(),
    ];

    $form['odata_endpoint_uri'] = array(
      '#type' => 'textfield',
      '#title' => t('Endpoint URI'),
      '#description' => t('The URL of the endpoint, e.g., http://example.com/odataservice/'),
      '#default_value' => $odata_entity->getEndpointUrl(),
      '#required' => TRUE,
//      '#disabled' => TRUE,
    );
    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $odata_entity = $this->entity;
    $status = $odata_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Odata entity.', [
          '%label' => $odata_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Odata entity.', [
          '%label' => $odata_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($odata_entity->toUrl('collection'));
  }

}
