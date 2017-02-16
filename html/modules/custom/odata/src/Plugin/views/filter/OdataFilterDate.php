<?php

namespace Drupal\odata\Plugin\views\filter;

/**
 * @file
 * Defines the various handler objects to help build and display oData views.
 */

/**
 * Handler to handle an oData Date filter.
 *
 * Extends views_handler_filter_date.
 */
class OdataFilterDate extends views_handler_filter_date {

  /**
   * Overrides operators().
   */
  public function operators() {
    $operators = array(
      'lt' => array(
        'title' => t('Is less than'),
        'method' => 'op_simple',
        'short' => t('<'),
        'values' => 1,
      ),
      'le' => array(
        'title' => t('Is less than or equal to'),
        'method' => 'op_simple',
        'short' => t('<='),
        'values' => 1,
      ),
      'eq' => array(
        'title' => t('Is equal to'),
        'method' => 'op_simple',
        'short' => t('='),
        'values' => 1,
      ),
      'ne' => array(
        'title' => t('Is not equal to'),
        'method' => 'op_simple',
        'short' => t('!='),
        'values' => 1,
      ),
      'ge' => array(
        'title' => t('Is greater than or equal to'),
        'method' => 'op_simple',
        'short' => t('>='),
        'values' => 1,
      ),
      'gt' => array(
        'title' => t('Is greater than'),
        'method' => 'op_simple',
        'short' => t('>'),
        'values' => 1,
      ),
      'between' => array(
        'title' => t('Is between'),
        'method' => 'op_between',
        'short' => t('between'),
        'values' => 2,
      ),
      'not between' => array(
        'title' => t('Is not between'),
        'method' => 'op_between',
        'short' => t('not between'),
        'values' => 2,
      ),
    );

    return $operators;
  }

  /**
   * Add a type selector to the value form.
   */
  public function value_form(&$form, &$form_state) {

    parent::value_form($form, $form_state);

    if (empty($form_state['exposed'])) {
      $form['value']['type']['#options'] = array(
        'date' => t('A date in a machine readable format. CCYY-MM-DDTHH:MM:SS is required.'),
        'offset' => t('An offset from the current time such as "!example1" or "!example2"', array('!example1' => '+1 day', '!example2' => '-2 hours -30 minutes')),
      );
      $form['value']['value']['#description'] = 'e.g.,' . format_date(time(), 'odata_datetime_format');
    }
  }

  /**
   * Validate if our user has entered a valid date.
   */
  public function options_validate(&$form, &$form_state) {
    parent::options_validate($form, $form_state);

    if (!empty($this->options['exposed']) && empty($form_state['values']['options']['expose']['required'])) {
      // Who cares what the value is if it's exposed and non-required.
      return;
    }

    $this->validate_valid_time($form['value'], $form_state['values']['options']['operator'], $form_state['values']['options']['value']);
  }

  /**
   * Validate that the time values convert to something usable.
   */
  public function validate_valid_time(&$form, $operator, $value) {
    $operators = $this->operators();
    if ($operators[$operator]['values'] == 1) {
      $convert = format_date(strtotime($value['value']), 'odata_datetime_format');
      if (!empty($form['value']) && ($convert == -1 || $convert === FALSE)) {
        form_error($form['value'], t('Invalid date format'));
      }
    }
    elseif ($operators[$operator]['values'] == 2) {
      $min = format_date(strtotime($value['value']), 'odata_datetime_format');
      if ($min == -1 || $min === FALSE) {
        form_error($form['min'], t('Invalid date format.'));
      }
      $max = format_date(strtotime($value['value']), 'odata_datetime_format');
      if ($max == -1 || $max === FALSE) {
        form_error($form['max'], t('Invalid date format.'));
      }
    }
  }

  /**
   * Overrides op_between().
   */
  function op_between($field) {
    $a = format_date(strtotime($this->value['min']), 'odata_datetime_format');
    $b = format_date(strtotime($this->value['max']), 'odata_datetime_format');

    switch ($this->options['operator']) {
      case 'not between':
        $expression = "$this->real_field+lt+datetime'$a'+or+$this->real_field+gt+datetime'$b'";
        break;

      default:
        $expression = "$this->real_field+gt+datetime'$a'+and+$this->real_field+lt+datetime'$b'";
        break;
    }

    $this->query->addWhereExpression($this->options['group'], $expression, NULL);
  }

  /**
   * Overrides op_simple().
   */
  function op_simple($field) {
    $value = format_date(strtotime($this->value['value']), 'odata_datetime_format');

    $this->query->addWhere($this->options['group'], $this->real_field, "datetime'$value'", "+$this->operator+");
  }
}
