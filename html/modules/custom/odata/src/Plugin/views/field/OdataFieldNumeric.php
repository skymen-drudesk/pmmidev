<?php

namespace Drupal\odata\Plugin\views\field;

/**
 * @file
 * Adds fields as numbers
 */

///**
// * Handler to handle an oData numeric field.
// */

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ViewExecutable;

/**
 * Displays numeric data.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("odata_field_numeric")
 */
class OdataFieldNumeric extends NumericField {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    // Always treat numbers as floats.
    $this->definition['float'] = TRUE;
  }

  /**
   * Overrides query().
   */
  public function query() {

    // Add the field.
    $this->field_alias = $this->query->addField($this->table, $this->realField, NULL);
  }

}
