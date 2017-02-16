<?php

namespace Drupal\odata\Plugin\views\argument;

/**
 * @file
 * This file contains the information of date arguments
 */

///**
// * Handler to handle a date argument.
// */
use Drupal\views\Plugin\views\argument\Date;

/**
 * Defines a contextual filter for applying Odata API conditions.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("odata_argument_date")
 */
class OdataArgumentDate extends Date {

  /**
   * Overrides query().
   */
  public function query($group_by = FALSE) {
    $this->query->addWhere(0, "$this->realField", $this->argument, '+eq+');
  }

}
