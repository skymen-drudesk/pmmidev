<?php

namespace Drupal\odata\Plugin\views\filter;

/**
 * @file
 * Definition of odata_handler_filter.
 */

/**
 * Handler to handle an oData filter.
 */
class OdataFilter extends views_handler_filter {

  /**
   * Overrides operator().
   */
  public function operator() {
    return $this->operator == 'eq' ? '+eq+' : '+ne+';
  }

  /**
   * Add this filter to the query.
   */
  public function query() {
    $this->query->addWhere($this->options['group'], "$this->real_field", $this->value, $this->operator);
  }
}
