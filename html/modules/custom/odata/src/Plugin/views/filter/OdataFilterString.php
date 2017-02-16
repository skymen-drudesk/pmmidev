<?php

namespace Drupal\odata\Plugin\views\filter;

/**
 * @file
 * Definition of odata_handler_filter_string.
 */

/**
 * Handler to handle an oData String filter.
 *
 * Extends views_handler_filter_string.
 */
class OdataFilterString extends views_handler_filter_string {

  /**
   * Overrides query().
   */
  public function operators() {
    $operators = array(
      'eq' => array(
        'title' => t('Is equal to'),
        'short' => t('='),
        'method' => 'op_equal',
        'values' => 1,
      ),
      'ne' => array(
        'title' => t('Is not equal to'),
        'short' => t('!='),
        'method' => 'op_equal',
        'values' => 1,
      ),
      'startswith' => array(
        'title' => t('Starts with'),
        'short' => t('begins'),
        'method' => 'op_starts',
        'values' => 1,
      ),
      'not_starts' => array(
        'title' => t('Does not start with'),
        'short' => t('not_begins'),
        'method' => 'op_not_starts',
        'values' => 1,
      ),
      'endswith' => array(
        'title' => t('Ends with'),
        'short' => t('ends'),
        'method' => 'op_ends',
        'values' => 1,
      ),
      'not_ends' => array(
        'title' => t('Does not end with'),
        'short' => t('not_ends'),
        'method' => 'op_not_ends',
        'values' => 1,
      ),
      'substringof' => array(
        'title' => t('Contains'),
        'short' => t('contains'),
        'method' => 'op_contains',
        'values' => 1,
      ),
      'not' => array(
        'title' => t('Does not contain'),
        'short' => t('!has'),
        'method' => 'op_not',
        'values' => 1,
      ),
      'length' => array(
        'title' => t('Length equals'),
        'short' => t('length ='),
        'method' => 'op_length',
        'values' => 1,
      ),
      'not_length' => array(
        'title' => t('Length not equals'),
        'short' => t('length !='),
        'method' => 'op_not_length',
        'values' => 1,
      ),
      'shorterthan' => array(
        'title' => t('Length is shorter than'),
        'short' => t('length <'),
        'method' => 'op_shorter',
        'values' => 1,
      ),
      'longerthan' => array(
        'title' => t('Length is longer than'),
        'short' => t('length >'),
        'method' => 'op_longer',
        'values' => 1,
      ),
    );
    return $operators;
  }

  /**
   * Overrides query().
   */
  public function query() {
    $field = "$this->real_field";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

  /**
   * Overrides operator().
   */
  public function operator() {
    return $this->operator == 'eq' ? '+eq+' : '+ne+';
  }

  /**
   * Overrides op_equal().
   */
  public function op_equal($field) {
    $this->query->addWhere($this->options['group'], $field, "'" . drupal_encode_path($this->value) . "'", $this->operator());
  }

  /**
   * Overrides op_starts().
   */
  public function op_starts($field) {
    $this->query->addWhere($this->options['group'], "startswith($field,'" . drupal_encode_path($this->value) . "')", "true", "+eq+");
  }

  /**
   * Overrides op_not_starts().
   */
  public function op_not_starts($field) {
    $this->query->addWhere($this->options['group'], "startswith($field,'" . drupal_encode_path($this->value) . "')", "false", "+eq+");
  }

  /**
   * Overrides op_ends().
   */
  public function op_ends($field) {
    $this->query->addWhere($this->options['group'], "endswith($field,'" . drupal_encode_path($this->value) . "')", "true", "+eq+");
  }

  /**
   * Overrides op_not_ends().
   */
  public function op_not_ends($field) {
    $this->query->addWhere($this->options['group'], "endswith($field,'" . drupal_encode_path($this->value) . "')", "false", "+eq+");
  }

  /**
   * Overrides op_contains().
   */
  public function op_contains($field) {
    $this->query->addWhere($this->options['group'], "substringof('" . drupal_encode_path($this->value) . "',$field)", "true", "+eq+");
  }

  /**
   * Overrides op_not().
   */
  public function op_not($field) {
    $this->query->addWhere($this->options['group'], "substringof('" . drupal_encode_path($this->value) . "',$field)", "false", "+eq+");
  }

  /**
   * Overrides op_length().
   */
  public function op_length($field) {
    $this->query->addWhere($this->options['group'], "length($field)", drupal_encode_path($this->value), '+eq+');
  }

  /**
   * Overrides op_not_length().
   */
  public function op_not_length($field) {
    $this->query->addWhere($this->options['group'], "length($field)", drupal_encode_path($this->value), '+ne+');
  }

  /**
   * Overrides op_shorter().
   */
  public function op_shorter($field) {
    $this->query->addWhere($this->options['group'], "length($field)", drupal_encode_path($this->value), '+lt+');
  }

  /**
   * Overrides op_longer().
   */
  public function op_longer($field) {
    $this->query->addWhere($this->options['group'], "length($field)", drupal_encode_path($this->value), '+gt+');
  }
}
