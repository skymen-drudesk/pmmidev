<?php

namespace Drupal\odata\Plugin\views\argument;

/**
 * @file
 * This file contains the information of string arguments
 */

/**
 * Handler to handle a string argument.
 */
class OdataArgumentString extends views_handler_argument_string {

  /**
   * Overrides query().
   */
  public function query($group_by = FALSE) {
    $this->query->addWhere(0, "$this->real_field", drupal_encode_path("'$this->argument'"), '+eq+');
  }
}
