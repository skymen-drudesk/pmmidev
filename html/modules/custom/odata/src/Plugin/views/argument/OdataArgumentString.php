<?php

namespace Drupal\odata\Plugin\views\argument;

/**
 * @file
 * This file contains the information of string arguments
 */

/**
 * Handler to handle a string argument.
 */
use Drupal\Component\Utility\UrlHelper;
use Drupal\views\Plugin\views\argument\StringArgument;

/**
 * Odata argument handler to implement string arguments that may have length
 * limits.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("odata_argument_string")
 */
class OdataArgumentString extends StringArgument {

  /**
   * Overrides query().
   */
  public function query($group_by = FALSE) {
    $this->query->addWhere(0, "$this->realField", UrlHelper::encodePath("'$this->argument'"), '+eq+');
  }

}
