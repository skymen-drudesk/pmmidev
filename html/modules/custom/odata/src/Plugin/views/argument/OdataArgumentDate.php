<?php

namespace Drupal\odata\Plugin\views\argument;

/**
 * @file
 * This file contains the information of date arguments
 */

/**
 * Handler to handle a date argument.
 */
class OdataArgumentDate extends views_handler_argument_date {

  /**
   * Overrides query().
   */
  public function query($group_by = FALSE) {
    $this->query->addWhere(0, "$this->real_field", $this->argument, '+eq+');
  }
}
