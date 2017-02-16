<?php

namespace Drupal\odata\Plugin\views\field;

/**
 * @file
 * Definition of odata_handler_filter.
 */

/**
 * Handler to handle an oData field.
 */
class OdataFieldComplex extends views_handler_field {

  /**
   * Overrides option_definition().
   */
  public function option_definition() {
    $options = parent::option_definition();

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
  public function options_form(&$form, &$form_state) {

    // If it's a complex field, make its properties
    // available for display.
    if ($this->definition['complex_fields']) {
      $form['complex_output'] = array(
        '#type' => 'select',
        '#title' => t("Show @title as:", array('@title' => $this->real_field)),
        '#options' => drupal_map_assoc(array('all' => t('All')) + array_keys($this->definition['complex_fields'])),
        '#description' => t('If you want custom display, please use the rewrite results option below.'),
      );
    }

    parent::options_form($form, $form_state);
  }

  /**
   * Overrides document_self_tokens().
   */
  public function document_self_tokens(&$tokens) {
    foreach (array_keys($this->definition['complex_fields']) as $id => $value) {
      $tokens['[' . $this->options['id'] . ':' . $value . ']'] = $value;
    }
  }

  /**
   * Overrides add_self_tokens().
   */
  public function add_self_tokens(&$tokens, $item) {
    foreach (array_keys($this->definition['complex_fields']) as $id => $value) {
      $tokens['[' . $this->options['id'] . ':' . $value . ']'] = $this->items[$value];
    }
  }

  /**
   * Overrides query().
   */
  public function query() {
    // Add the field.
    $this->field_alias = $this->query->addField($this->table_alias, $this->real_field, NULL);
  }

  /**
   * Overrides pre_render().
   */
  public function pre_render(&$values) {

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
  public function render($values) {
    $value = $this->get_value($values);
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
