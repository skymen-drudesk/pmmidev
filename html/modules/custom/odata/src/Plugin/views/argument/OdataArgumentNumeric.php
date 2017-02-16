<?php

namespace Drupal\odata\Plugin\views\argument;

/**
 * @file
 * This file contains the information of numeric arguments
 */

///**
// * Handler to handle a numeric argument.
// */
use Drupal\views\Plugin\views\argument\NumericArgument;

/**
 * Defines a contextual filter for applying Odata API conditions.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("odata_argument_numeric")
 */
class OdataArgumentNumeric extends NumericArgument {

  /**
   * Overrides query().
   */
  public function query($group_by = FALSE) {
    $this->query->addWhere(0, "$this->realField", $this->argument, '+eq+');
  }

}
