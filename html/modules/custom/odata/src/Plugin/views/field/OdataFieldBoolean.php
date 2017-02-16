<?php

namespace Drupal\odata\Plugin\views\field;

/**
 * @file
 * Definition of odata_handler_field_boolean.
 */

/**
 * Handler to handle an oData boolean field.
 */
class OdataFieldBoolean extends views_handler_field_boolean {

  /**
   * Overrides query().
   */
  public function query() {
    // Add the field.
    $this->field_alias = $this->query->addField($this->table_alias, $this->real_field, NULL);
  }
}
