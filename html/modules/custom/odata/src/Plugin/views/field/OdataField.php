<?php

namespace Drupal\odata\Plugin\views\field;

/**
 * @file
 * Definition of odata_handler_filter.
 */
use Drupal\views\Plugin\views\field\Field;

///**
// * Handler to handle an oData field.
// */
/**
 * Displays entity field data.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("odata_field")
 */
class OdataField extends Field {

  /**
   * Overrides query().
   */
  public function query() {
    // Add the field.
    $this->field_alias = $this->query->addField($this->table, $this->realField, NULL);
  }

}
