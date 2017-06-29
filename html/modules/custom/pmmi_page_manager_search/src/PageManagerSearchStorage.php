<?php

namespace Drupal\pmmi_page_manager_search;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\pmmi_page_manager_search\Entity\PageManagerSearch;

class PageManagerSearchStorage extends ContentEntityStorageBase implements EntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    return parent::readFieldItemsToPurge($field_definition, $batch_size);
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
    parent::purgeFieldItems($entity, $field_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {
    return parent::doLoadRevisionFieldItems($revision_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
    parent::doSaveFieldItems($entity, $names);
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
    parent::doDeleteFieldItems($entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
    parent::doDeleteRevisionFieldItems($revision);
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    return parent::doLoadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.null';
  }

  /**
   * {@inheritdoc}
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    return parent::countFieldData($storage_definition, $as_bool);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    return PageManagerSearch::load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    return PageManagerSearch::loadMultiple($ids);
  }
}