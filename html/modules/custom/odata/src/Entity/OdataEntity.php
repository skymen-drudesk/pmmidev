<?php

namespace Drupal\odata\Entity;

use Drupal\bootstrap\Utility\Crypt;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Odata entity entity.
 *
 * @ConfigEntityType(
 *   id = "odata_entity",
 *   label = @Translation("Odata entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\odata\OdataEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\odata\Form\OdataEntityForm",
 *       "edit" = "Drupal\odata\Form\OdataEntityForm",
 *       "delete" = "Drupal\odata\Form\OdataEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\odata\OdataEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "odata_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/odata_entity/{odata_entity}",
 *     "add-form" = "/admin/structure/odata_entity/add",
 *     "edit-form" = "/admin/structure/odata_entity/{odata_entity}/edit",
 *     "delete-form" = "/admin/structure/odata_entity/{odata_entity}/delete",
 *     "collection" = "/admin/structure/odata_entity"
 *   }
 * )
 */
class OdataEntity extends ConfigEntityBase implements OdataEntityInterface {

  /**
   * The Odata entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Odata entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Odata entity Endpoint Uri.
   *
   * @var string
   */
  protected $odata_endpoint_uri;

  /**
   * The Odata Auth hash string.
   *
   * @var string
   */
  protected $odata_username;

  /**
   * The Odata Auth hash string.
   *
   * @var string
   */
  protected $odata_password;

  /**
   * The Odata Auth hash string.
   *
   * @var string
   */
  protected $odata_collection;

  /**
   * The Odata Auth hash string.
   *
   * @var string
   */
  protected $odata_collections_schema;

  /**
   * {@inheritdoc}
   */
  public function getEndpointUrl() {
    return isset($this->odata_endpoint_uri) ? $this->odata_endpoint_uri : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthHash() {
    if (isset($this->odata_username) && isset($this->odata_password)) {
      return base64_encode($this->getUsername() . ':' . $this->getPassword());
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return isset($this->odata_username) ? $this->odata_username : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return isset($this->odata_password) ? $this->odata_password : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollection() {
    return isset($this->odata_collection) ? $this->odata_collection : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionSchema() {
    return isset($this->odata_collections_schema) ? $this->odata_collections_schema : NULL;
  }

}
