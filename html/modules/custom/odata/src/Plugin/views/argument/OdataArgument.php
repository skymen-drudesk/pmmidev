<?php

namespace Drupal\odata\Plugin\views\argument;

/**
 * @file
 * Definition of odata_handler_field.
 */

/**
 * Handler to handle an oData argument field.
 */
class OdataArgument extends views_handler_argument {

  /**
   * Set up the query for this argument.
   *
   * The argument sent may be found at $this->argument.
   */
  public function query($group_by = FALSE) {
    $this->query->addWhere(0, "$this->real_field", drupal_encode_path("'$this->argument'"), '+eq+');
  }
}
