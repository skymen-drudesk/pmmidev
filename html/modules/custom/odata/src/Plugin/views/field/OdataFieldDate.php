<?php

namespace Drupal\odata\Plugin\views\field;

/**
 * @file
 * Definition of odata_handler_field_date.
 */

/**
 * Handler to handle an oData date field.
 */
class OdataFieldDate extends views_handler_field_date {

  /**
   * Overrides query().
   */
  public function query() {
    // Add the field.
    $this->field_alias = $this->query->addField($this->table_alias, $this->real_field, NULL);
  }

  /**
   * Overrides render().
   */
  public function render($values) {

    // Unfortunatelty oData can sent date as "\/Date(number of ticks)\/".
    // The number of ticks is a positive or negative long
    // value that indicates the number of ticks (milliseconds) that have
    // elapsed since midnight 01 January, 1970 UTC.
    $value = $this->get_value($values);
    preg_match('/\/Date\(([0-9]+)\)\//', $value, $matches);
    if (isset($matches['1']) && is_numeric($matches['1'])) {
      // Convert millisecs to secs.
      $values->{$this->field_alias} = round($matches['1'] / 1000);
    }
    else {
      $values->{$this->field_alias} = date('U', strtotime($this->get_value($values)));
    }
    return parent::render($values);
  }
}
