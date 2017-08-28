<?php

namespace Drupal\pmmi_page_manager_search\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;


/**
 * Defines the Page manager search entity.
 *  Emulate entity for making Page Variant entity searchable.
 *
 * @ingroup pmmi_page_manager_search
 *
 * @ContentEntityType(
 *   id = "pmmi_page_manager_search",
 *   label = @Translation("Page Manager Search"),
 *   base_table = "pmmi_page_manager_search",
 *   entity_keys = {
 *    "id" = "id",
 *    "label" = "name",
 *    "uuid" = "uuid"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pmmi_page_manager_search\PageManagerSearchEntityListBuilder",
 *     "views_data" = "Drupal\pmmi_page_manager_search\Entity\PageManagerSearchEntityViewsData",
 *   },
 *   links = {
 *     "canonical" = "/page-manager-search/{pmmi_page_manager_search}"
 *  },
 *  admin_permission = "administer Page Manager Search entity",
 *  field_ui_base_route = "pmmi_page_manager_search.settings"
 * )
 */

class PageManagerSearch extends ContentEntityBase implements ContentEntityInterface {

  /**
   * Get referenced Page Variant Entity.
   */
  public function getPageVariant() {
    return $this->get('pid')->entity;
  }

  /**
   * Get referenced Page Variant Id.
   */
  public function getPageVariantId() {
    return $this->get('pid')->target_id;
  }

  /**
   * Get referenced Page Variant Id.
   */
  public function setPageVariantId($pid) {
    return $this->set('pid', $pid);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Page Manager Search entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Page Manager Search entity.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the page.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Content'))
      ->setDescription(t('The content of the page.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);;

    $fields['pid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Page variant ID'))
      ->setDescription(t('Page variant ID of the referenced Page.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Page Variant path'))
      ->setDescription(t('The path to the Page Variant.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ));

    return $fields;
  }
}
