<?php

namespace Drupal\odata\Plugin\views\argument;

/**
 * @file
 * This file contains the information of boolean arguments
 */

/**
 * Handler to handle a boolean argument.
 */
class OdataArgumentBoolean extends OdataArgumentString {

  /**
   * Overrides query().
   */
  public function query($group_by = FALSE) {
    $this->query->addWhere(0, "$this->real_field", drupal_encode_path(drupal_strtolower($this->argument)), '+eq+');
  }
}
