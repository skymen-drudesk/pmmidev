<?php

namespace Drupal\odata\Plugin\views\sort;

/**
 * @file
 * Definition of odata_handler_sort.
 */

/**
 * Handler to handle an oData sort.
 */
class OdataSort extends views_handler_sort {

  /**
   * Overrides query().
   */
  public function query() {
    // Add the field.
    $this->query->add_orderby($this->table_alias, $this->real_field, $this->options['order']);
  }
}
