<?php

namespace Drupal\pmmi_page_manager_search\Plugin\search_api\datasource;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Datasource\DatasourcePluginBase;

/**
 * Class PageManagerSearchDatasource
 *
 * @SearchApiDatasource(
 *   id = "page_manager_search_datasource",
 *   label = @Translation("Page Manager Search Datasource"),
 *   description = @Translation("Custom datasource for Page Manager search entity."),
 * )
 * @package Drupal\pmmi_page_manager_search\Plugin\search_api\datasource
 */
class PageManagerSearchDatasource extends DatasourcePluginBase implements DatasourceInterface{

  /**
   * Retrieves the unique ID of an object from this datasource.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   An object from this datasource.
   *
   * @return string|null
   *   The datasource-internal, unique ID of the item. Or NULL if the given item
   *   is no valid item of this datasource.
   */
  public function getItemId(ComplexDataInterface $item) {
    // TODO: Implement getItemId() method.
  }

  public function load($id) {
    return \Drupal::entityTypeManager()
      ->getStorage('page_manager_search')
      ->load($id);
  }

  public function loadMultiple(array $ids = NULL) {
    return \Drupal::entityTypeManager()
      ->getStorage('page_manager_search')
      ->loadMultiple($ids);
  }

}