<?php

namespace Drupal\odata\Plugin\views\argument;

/**
 * @file
 * This file contains the information of numeric arguments
 */

/**
 * Handler to handle a numeric argument.
 */
class OdataArgumentNumeric extends views_handler_argument_numeric {

  /**
   * Overrides query().
   */
  public function query($group_by = FALSE) {
    $this->query->addWhere(0, "$this->real_field", $this->argument, '+eq+');
  }
}
