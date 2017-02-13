<?php

namespace Drupal\odata\Entity;

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
   * {@inheritdoc}
   */
  public function getEndpointUrl() {
    return isset($this->odata_endpoint_uri) ? $this->odata_endpoint_uri : NULL;
  }

}
