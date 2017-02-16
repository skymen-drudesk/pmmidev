<?php

namespace Drupal\odata\Plugin\views\argument;

/**
 * @file
 * This file contains the information of boolean arguments
 */

///**
// * Handler to handle a boolean argument.
// */
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;

/**
 * Defines a contextual filter for applying Odata API conditions.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("odata_argument_boolean")
 */
class OdataArgumentBoolean extends OdataArgumentString {

  /**
   * Overrides query().
   */
  public function query($group_by = FALSE) {
    $this->query->addWhere(0, "$this->realField", UrlHelper::encodePath(Unicode::strtolower($this->argument)), '+eq+');
  }

}
