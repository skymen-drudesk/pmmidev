<?php

namespace Drupal\odata\Plugin\views\sort;

///**
// * @file
// * Definition of odata_handler_sort.
// */
use Drupal\views\Plugin\views\sort\SortPluginBase;

///**
// * Handler to handle an oData sort.
// */
/**
 * Provides a sort plugin for Odata views.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("odata_sort")
 */
class OdataSort extends SortPluginBase {

  /**
   * Overrides query().
   */
  public function query() {
    // Add the field.
    $this->query->addOrderBy($this->table, $this->realField, $this->options['order']);
  }

}
