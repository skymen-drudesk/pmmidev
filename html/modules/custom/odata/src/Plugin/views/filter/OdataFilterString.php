<?php

namespace Drupal\odata\Plugin\views\filter;

///**
// * @file
// * Definition of odata_handler_filter_string.
// */
use Drupal\Component\Utility\UrlHelper;
use Drupal\views\Plugin\views\filter\StringFilter;

///**
// * Handler to handle an oData String filter.
// *
// * Extends views_handler_filter_string.
// */

/**
 * Basic textfield filter to handle an oData String filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("odata_filter_string")
 */
class OdataFilterString extends StringFilter {

  /**
   * Overrides query().
   */
  public function operators() {
    $operators = array(
      'eq' => array(
        'title' => t('Is equal to'),
        'short' => t('='),
        'method' => 'opEqual',
        'values' => 1,
      ),
      'ne' => array(
        'title' => t('Is not equal to'),
        'short' => t('!='),
        'method' => 'opEqual',
        'values' => 1,
      ),
      'startswith' => array(
        'title' => t('Starts with'),
        'short' => t('begins'),
        'method' => 'opStartsWith',
        'values' => 1,
      ),
      'not_starts' => array(
        'title' => t('Does not start with'),
        'short' => t('not_begins'),
        'method' => 'opNotStartsWith',
        'values' => 1,
      ),
      'endswith' => array(
        'title' => t('Ends with'),
        'short' => t('ends'),
        'method' => 'opEndsWith',
        'values' => 1,
      ),
      'not_ends' => array(
        'title' => t('Does not end with'),
        'short' => t('not_ends'),
        'method' => 'opNotEndsWith',
        'values' => 1,
      ),
      'substringof' => array(
        'title' => t('Contains'),
        'short' => t('contains'),
        'method' => 'opContains',
        'values' => 1,
      ),
      'not' => array(
        'title' => t('Does not contain'),
        'short' => t('!has'),
        'method' => 'opNotLike',
        'values' => 1,
      ),
      'length' => array(
        'title' => t('Length equals'),
        'short' => t('length ='),
        'method' => 'opLength',
        'values' => 1,
      ),
      'not_length' => array(
        'title' => t('Length not equals'),
        'short' => t('length !='),
        'method' => 'opNotLength',
        'values' => 1,
      ),
      'shorterthan' => array(
        'title' => t('Length is shorter than'),
        'short' => t('length <'),
        'method' => 'opShorterThan',
        'values' => 1,
      ),
      'longerthan' => array(
        'title' => t('Length is longer than'),
        'short' => t('length >'),
        'method' => 'opLongerThan',
        'values' => 1,
      ),
    );
    return $operators;
  }

  /**
   * Overrides query().
   */
  public function query() {
    $field = "$this->realField";

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
   * Overrides opEqual().
   */
  public function opEqual($field) {
    $this->query->addWhere($this->options['group'], $field, "'" . UrlHelper::encodePath($this->value) . "'", $this->operator());
  }

  /**
   * Overrides opStartsWith().
   */
  public function opStartsWith($field) {
    $this->query->addWhere($this->options['group'], "startswith($field,'" . UrlHelper::encodePath($this->value) . "')", "true", "+eq+");
  }

  /**
   * Overrides opNotStartsWith().
   */
  public function opNotStartsWith($field) {
    $this->query->addWhere($this->options['group'], "startswith($field,'" . UrlHelper::encodePath($this->value) . "')", "false", "+eq+");
  }

  /**
   * Overrides opEndsWith().
   */
  public function opEndsWith($field) {
    $this->query->addWhere($this->options['group'], "endswith($field,'" . UrlHelper::encodePath($this->value) . "')", "true", "+eq+");
  }

  /**
   * Overrides opNotEndsWith().
   */
  public function opNotEndsWith($field) {
    $this->query->addWhere($this->options['group'], "endswith($field,'" . UrlHelper::encodePath($this->value) . "')", "false", "+eq+");
  }

  /**
   * Overrides opContains().
   */
  public function opContains($field) {
    $this->query->addWhere($this->options['group'], "substringof('" . UrlHelper::encodePath($this->value) . "',$field)", "true", "+eq+");
  }

  /**
   * Overrides opNotLike().
   */
  public function opNotLike($field) {
    $this->query->addWhere($this->options['group'], "substringof('" . UrlHelper::encodePath($this->value) . "',$field)", "false", "+eq+");
  }

  /**
   * Overrides opLength().
   */
  public function opLength($field) {
    $this->query->addWhere($this->options['group'], "length($field)", UrlHelper::encodePath($this->value), '+eq+');
  }

  /**
   * Overrides opNotLength().
   */
  public function opNotLength($field) {
    $this->query->addWhere($this->options['group'], "length($field)", UrlHelper::encodePath($this->value), '+ne+');
  }

  /**
   * Overrides opShorterThan().
   */
  public function opShorterThan($field) {
    $this->query->addWhere($this->options['group'], "length($field)", UrlHelper::encodePath($this->value), '+lt+');
  }

  /**
   * Overrides opLongerThan().
   */
  public function opLongerThan($field) {
    $this->query->addWhere($this->options['group'], "length($field)", UrlHelper::encodePath($this->value), '+gt+');
  }

}
