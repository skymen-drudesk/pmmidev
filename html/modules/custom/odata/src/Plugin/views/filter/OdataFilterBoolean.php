<?php

namespace Drupal\odata\Plugin\views\filter;

/**
 * @file
 * Definition of odata_handler_filter_boolean_operator.
 */

/**
 * Handler to handle an oData Boolean filter.
 */
class OdataFilterBoolean extends views_handler_filter_boolean_operator {

  /**
   * Overrides query().
   */
  public function query() {
    if ($this->value == 1) {
      $this->query->addWhere($this->options['group'], $this->real_field, 'true', '+eq+');
    }
    else {
      $this->query->addWhere($this->options['group'], $this->real_field, 'false', '+eq+');
    }
  }
}
