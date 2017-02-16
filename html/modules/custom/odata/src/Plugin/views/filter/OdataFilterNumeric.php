<?php

namespace Drupal\odata\Plugin\views\filter;

///**
// * @file
// * Definition of odata_handler_filter_numeric.
// */
use Drupal\views\Plugin\views\filter\NumericFilter;

///**
// * Handler to handle an oData Numeric filter.
// *
// * Extends views_handler_filter_numeric
// */
/**
 * Simple filter to handle an oData Numeric filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("odata_filter_numeric")
 */
class OdataFilterNumeric extends NumericFilter {

  /**
   * Overrides operators().
   */
  public function operators() {
    $operators = array(
      'lt' => array(
        'title' => t('Is less than'),
        'method' => 'opSimple',
        'short' => t('<'),
        'values' => 1,
      ),
      'le' => array(
        'title' => t('Is less than or equal to'),
        'method' => 'opSimple',
        'short' => t('<='),
        'values' => 1,
      ),
      'eq' => array(
        'title' => t('Is equal to'),
        'method' => 'opSimple',
        'short' => t('='),
        'values' => 1,
      ),
      'ne' => array(
        'title' => t('Is not equal to'),
        'method' => 'opSimple',
        'short' => t('!='),
        'values' => 1,
      ),
      'ge' => array(
        'title' => t('Is greater than or equal to'),
        'method' => 'opSimple',
        'short' => t('>='),
        'values' => 1,
      ),
      'gt' => array(
        'title' => t('Is greater than'),
        'method' => 'opSimple',
        'short' => t('>'),
        'values' => 1,
      ),
      'between' => array(
        'title' => t('Is between'),
        'method' => 'opBetween',
        'short' => t('between'),
        'values' => 2,
      ),
      'not between' => array(
        'title' => $this->t('Is not between'),
        'method' => 'opBetween',
        'short' => $this->t('not between'),
        'values' => 2,
      ),
    );

    // If the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += array(
        'empty' => array(
          'title' => $this->t('Is empty (NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('empty'),
          'values' => 0,
        ),
        'not empty' => array(
          'title' => $this->t('Is not empty (NOT NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('not empty'),
          'values' => 0,
        ),
      );
    }

    return $operators;
  }

  /**
   * Add this filter to the query.
   */
  public function query() {
    $field = $this->realField;

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

  /**
   * Overrides op_simple().
   */
  public function opSimple($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value['value'], "+$this->operator+");
  }

  /**
   * Overrides op_between().
   */
  public function opBetween($field) {
    if ($this->operator == 'between') {
      $this->query->addWhere($this->options['group'], "($field+gt+" . $this->value['min'] . "+and+$field+lt+" . $this->value['max'] . ")", NULL);
    }
    else {
      $this->query->addWhere($this->options['group'], "($field+lt+" . $this->value['min'] . "+or+$field+gt+" . $this->value['max'] . ")", NULL);
    }
  }

}
