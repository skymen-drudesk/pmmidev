<?php

namespace Drupal\odata\Plugin\views\field;

/**
 * @file
 * Definition of odata_handler_filter.
 */

/**
 * Handler to handle an oData field.
 */
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\Field;
use Drupal\views\ResultRow;

///**
// * Handler to handle an oData field.
// */
/**
 * Displays entity field data.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("odata_field_complex")
 */
class OdataFieldComplex extends Field {

  /**
   * Overrides option_definition().
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    if ($this->definition['complex_fields']) {
      foreach (array_keys($this->definition['complex_fields']) as $value) {
        $options['complex_output'] = array('default' => 'Street');
      }
    }

    return $options;
  }

  /**
   * Overrides options_form().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    // If it's a complex field, make its properties
    // available for display.
    if ($this->definition['complex_fields']) {
      $form['complex_output'] = array(
        '#type' => 'select',
        '#title' => t("Show @title as:", array('@title' => $this->realField)),
        '#options' => drupal_map_assoc(array('all' => t('All')) + array_keys($this->definition['complex_fields'])),
        '#description' => t('If you want custom display, please use the rewrite results option below.'),
      );
    }

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Overrides document_self_tokens().
   */
  public function documentSelfTokens(&$tokens) {
    foreach (array_keys($this->definition['complex_fields']) as $id => $value) {
      $tokens['[' . $this->options['id'] . ':' . $value . ']'] = $value;
    }
  }

  /**
   * Overrides add_self_tokens().
   */
  public function addSelfTokens(&$tokens, $item) {
    foreach (array_keys($this->definition['complex_fields']) as $id => $value) {
      $tokens['[' . $this->options['id'] . ':' . $value . ']'] = $this->items[$value];
    }
  }

  /**
   * Overrides query().
   */
  public function query() {
    // Add the field.
    $this->field_alias = $this->query->addField($this->table, $this->realField, NULL);
  }

  /**
   * Overrides pre_render().
   */
  public function prerender(&$values) {

    // Populate complex tokens.
    foreach ($values as $property) {
      if ($property->{$this->field_alias}) {
        $this->items = $property->{$this->field_alias};
      }
    }
  }

  /**
   * Overrides render().
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if ($this->definition['complex_fields']) {
      if ($this->options['complex_output'] == 'All') {
        return implode(" ", $value);
      }
      else {
        return $value[$this->options['complex_output']];
      }
    }
  }

}
