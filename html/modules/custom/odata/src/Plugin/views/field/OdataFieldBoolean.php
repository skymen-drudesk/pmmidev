<?php

namespace Drupal\odata\Plugin\views\field;

/**
 * @file
 * Definition of odata_handler_field_boolean.
 */
use Drupal\views\Plugin\views\field\Boolean;

/**
 * Handler to handle an oData boolean field.
 */
/**
 * Handles the display of boolean fields in Odata Views.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("odata_field_boolean")
 */
class OdataFieldBoolean extends Boolean {

  /**
   * Overrides query().
   */
  public function query() {
    // Add the field.
    $this->field_alias = $this->query->addField($this->table, $this->realField, NULL);
  }

}
