<?php

namespace Drupal\odata\Plugin\views\filter;

/**
 * @file
 * Definition of odata_handler_filter_boolean_operator.
 */

///**
// * Handler to handle an oData Boolean filter.
// */
use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Defines a filter for filtering on boolean values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("odata_filter_boolean")
 */
class OdataFilterBoolean extends BooleanOperator {

  /**
   * Overrides query().
   */
  public function query() {
    if ($this->value == 1) {
      $this->query->addWhere($this->options['group'], $this->realField, 'true', '+eq+');
    }
    else {
      $this->query->addWhere($this->options['group'], $this->realField, 'false', '+eq+');
    }
  }

}
