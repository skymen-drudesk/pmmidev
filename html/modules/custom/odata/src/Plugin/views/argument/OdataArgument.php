<?php

namespace Drupal\odata\Plugin\views\argument;

/**
 * @file
 * Definition of odata_handler_field.
 */


use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

///**
// * Handler to handle an oData argument field.
// */
/**
 * Defines a contextual filter for applying Odata API conditions.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("odata_argument")
 */
class OdataArgument extends ArgumentPluginBase {

  use UncacheableDependencyTrait;

  /**
   * The Views query object used by this contextual filter.
   *
   * @var \Drupal\odata\Plugin\views\query\OdataPluginQuery
   */
  public $query;

  /**
   * Set up the query for this argument.
   *
   * The argument sent may be found at $this->argument.
   */
  public function query($group_by = FALSE) {
    $this->query->addWhere(0, "$this->real_field", UrlHelper::encodePath("'$this->argument'"), '+eq+');
  }

}
