<?php

namespace Drupal\odata\Plugin\views\field;

/**
 * @file
 * Definition of odata_handler_filter.
 */

/**
 * Handler to handle an oData field.
 */
class OdataFieldString extends views_handler_field {

  /**
   * Overrides query().
   */
  public function query() {
    // Add the field.
    $this->field_alias = $this->query->addField($this->table_alias, $this->real_field, NULL);
  }
}
