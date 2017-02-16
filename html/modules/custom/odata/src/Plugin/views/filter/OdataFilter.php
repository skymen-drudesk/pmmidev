<?php

namespace Drupal\odata\Plugin\views\filter;

/**
 * @file
 * Definition of odata_handler_filter.
 */

/**
 * Handler to handle an oData filter.
 */
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Defines a filter for filtering on boolean values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("odata_filter")
 */
class OdataFilter extends FilterPluginBase {

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
    $this->query->addWhere($this->options['group'], "$this->realField", $this->value, $this->operator);
  }

}
