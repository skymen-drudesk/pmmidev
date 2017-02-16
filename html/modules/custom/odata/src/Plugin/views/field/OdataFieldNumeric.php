<?php

namespace Drupal\odata\Plugin\views\field;

/**
 * @file
 * Adds fields as numbers
 */

/**
 * Handler to handle an oData numeric field.
 */
class OdataFieldNumeric extends views_handler_field_numeric {

  /**
   * Overrides init().
   */
  public function init(&$view, &$options) {
    parent::init($view, $options);
    // Always treat numbers as floats.
    $this->definition['float'] = TRUE;
  }

  /**
   * Overrides query().
   */
  public function query() {

    // Add the field.
    $this->field_alias = $this->query->addField($this->table_alias, $this->real_field, NULL);
  }
}
