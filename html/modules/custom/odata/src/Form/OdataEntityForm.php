<?php

namespace Drupal\odata\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\odata\Service\OdataParser;
use GuzzleHttp\Psr7\Stream;

/**
 * Class OdataEntityForm.
 *
 * @package Drupal\odata\Form
 */
class OdataEntityForm extends EntityForm {

  protected $step = 1;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\odata\Entity\OdataEntity $odata_entity */
    $odata_entity = $this->entity;
    $this->step = $odata_entity->isNew() ? $this->step : 2;
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

    $form['odata_endpoint_uri'] = [
      '#type' => 'textfield',
      '#title' => t('Endpoint URI'),
      '#description' => t('The URL of the endpoint, e.g., http://example.com/odataservice.svc'),
      '#default_value' => $odata_entity->getEndpointUrl(),
      '#required' => TRUE,
    ];
    $form['odata_accept_request_header'] = array(
      '#type' => 'radios',
      '#title' => t('Accept Request Header'),
      '#options' => array(
        'xml' => 'XML',
        'json' => 'JSON',
//        'json_light' => 'JSON Light',
//        'json_light' => 'JSON Light',
      ),
      '#required' => TRUE,
      '#default_value' => $odata_entity->getRequestFormat(),
      '#description' => t('Indicates that the request is specifically limited to the desired type.'),
    );
    $form['auth_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required Authentification'),
      '#default_value' => $odata_entity->getAuthHash() != NULL ? TRUE : FALSE,
    ];
    $form['odata_username'] = [
      '#type' => 'textfield',
      '#title' => t('Odata Username'),
      '#description' => t('Username for the endpoint'),
      '#default_value' => $odata_entity->getUsername(),
      '#states' => array(
        'invisible' => array(
          ':input[name="auth_required"]' => array('checked' => FALSE),
        ),
        'required' => array(
          ':input[name="auth_required"]' => array('checked' => TRUE),
        ),
      ),
    ];

    $form['odata_password'] = [
      '#type' => 'textfield',
      '#title' => t('Odata Password'),
      '#description' => t('Password for the endpoint'),
      '#default_value' => $odata_entity->getPassword(),
      '#states' => array(
        'invisible' => array(
          ':input[name="auth_required"]' => array('checked' => FALSE),
        ),
        'required' => array(
          ':input[name="auth_required"]' => array('checked' => TRUE),
        ),
      ),
    ];

    if ($this->step == 2) {
      $form['odata_endpoint_uri']['#disabled'] = TRUE;
      $form['auth_required']['#disabled'] = TRUE;
      $form['odata_username']['#disabled'] = TRUE;
      $form['odata_password']['#disabled'] = TRUE;
      $this->buildOptions($form);

      if ($this->operation == 'add' && $form_state->hasTemporaryValue('odata_collection')) {
        $this->buildOptions($form);
        $form['odata_collection']['#options'] = $form_state->getTemporaryValue('odata_collection');
        $form['odata_collections_schema']['#value'] = serialize($form_state->getTemporaryValue('odata_collections_schema'));
      }
      elseif ($this->operation == 'edit' && $schema = $odata_entity->getCollectionSchema()) {
        $this->buildOptions($form);
        $form['odata_collection']['#disabled'] = TRUE;
        $odata_collection = $odata_entity->getCollection();
        $form['odata_collection']['#default_value'] = $odata_collection;
        $form['odata_collection']['#options'] = [$odata_collection => $odata_collection];
        $form['odata_collections_schema']['#value'] = $schema;
      }
      else {
        drupal_set_message(t("We weren't able to find any available collection sets"), 'error');
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildOptions(&$form) {
    $form['odata_collection'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('Available collection sets'),
      '#description' => t('Select the collection you want to add.'),
    );
    $form['odata_collections_schema'] = array(
      '#type' => 'hidden',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => array('::submitForm', '::save'),
    );
    if ($this->step == 1) {
      $actions['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#submit' => array('::submitForm'),
      );
    }

    if (!$this->entity->isNew() && $this->entity->hasLinkTemplate('delete-form')) {
      $route_info = $this->entity->toUrl('delete-form');
      if ($this->getRequest()->query->has('destination')) {
        $query = $route_info->getOption('query');
        $query['destination'] = $this->getRequest()->query->get('destination');
        $route_info->setOption('query', $query);
      }
      $actions['delete'] = array(
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#access' => $this->entity->access('delete'),
        '#attributes' => array(
          'class' => array('button', 'button--danger'),
        ),
      );
      $actions['delete']['#url'] = $route_info;
    }

    return $actions;
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
    // Remove button and internal Form API values from submitted values.
    if ($this->step == 1) {
      $odata_endpoint_uri = $form_state->get('odata_endpoint_uri');
      // Remove / from the end of the URI.
      if (Unicode::substr($odata_endpoint_uri, -1) == '/') {
        $odata_endpoint_uri = rtrim($odata_endpoint_uri, '/');
      }
      $form_state->setValue('odata_endpoint_uri', $odata_endpoint_uri);
      $form_state->setRebuild();
      $odata = \Drupal::service('odata.manager');
      $request_data = new \stdClass();
      $request_data->entity = $this->entity;
      $data = $odata->getDataRequest($request_data, 'xml', TRUE);
      if (!$data instanceof Stream) {
        $form_state->setRebuild(TRUE);
      }
      else {
        $collections = new OdataParser($data);
        $entity_names = $collections->GetEntityTypes();
        if (!empty($entity_names)) {
          $properties = $collections->GetPropertiesPerEntity();
          $form_state->setTemporaryValue('odata_collection', $entity_names);
          $form_state->setTemporaryValue('odata_collections_schema', $properties);
          $this->step++;
        }
        else {
          drupal_set_message(t("We weren't able to find any available collection sets"), 'error');
          $form_state->setRebuild(TRUE);
        }
      }
    }
    else {
      $form_state->cleanValues();
      $this->entity = $this->buildEntity($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\odata\Entity\OdataEntity $odata_entity */
    $odata_entity = $this->entity;
    // Delete unused data from $metadata array.
    if ($this->operation == 'add') {
      $default_schema = unserialize($odata_entity->getCollectionSchema());
      if (!empty($default_schema) && array_key_exists($odata_entity->getCollection(), $default_schema)) {
        $schema = serialize($default_schema[$odata_entity->getCollection()]);
      }
      else {
        $schema = NULL;
      }
      $odata_entity->setCollectionSchema($schema);
    }
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
