<?php

namespace Drupal\odata\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Date;

///**
// * @file
// * Defines the various handler objects to help build and display oData views.
// */
//
///**
// * Handler to handle
// *
// * Extends views_handler_filter_date.
// */
/**
 * Filter to handle an oData Date filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("odata_filter_date")
 */
class OdataFilterDate extends Date {

  /**
   * Overrides operators().
   */
  public function operators() {
    $operators = array(
      'lt' => array(
        'title' => $this->t('Is less than'),
        'method' => 'opSimple',
        'short' => $this->t('<'),
        'values' => 1,
      ),
      'le' => array(
        'title' => $this->t('Is less than or equal to'),
        'method' => 'opSimple',
        'short' => $this->t('<='),
        'values' => 1,
      ),
      'eq' => array(
        'title' => $this->t('Is equal to'),
        'method' => 'opSimple',
        'short' => $this->t('='),
        'values' => 1,
      ),
      'ne' => array(
        'title' => $this->t('Is not equal to'),
        'method' => 'opSimple',
        'short' => $this->t('!='),
        'values' => 1,
      ),
      'ge' => array(
        'title' => $this->t('Is greater than or equal to'),
        'method' => 'opSimple',
        'short' => $this->t('>='),
        'values' => 1,
      ),
      'gt' => array(
        'title' => $this->t('Is greater than'),
        'method' => 'opSimple',
        'short' => $this->t('>'),
        'values' => 1,
      ),
      'between' => array(
        'title' => $this->t('Is between'),
        'method' => 'opBetween',
        'short' => $this->t('between'),
        'values' => 2,
      ),
      'not between' => array(
        'title' => $this->t('Is not between'),
        'method' => 'opBetween',
        'short' => $this->t('not between'),
        'values' => 2,
      ),
    );

    return $operators;
  }

  /**
   * Add a type selector to the value form.
   */
  public function valueForm(&$form, FormStateInterface $form_state) {

    parent::valueForm($form, $form_state);

    if (!$form_state->get('exposed')) {
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
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    if (!empty($this->options['exposed']) && empty($form_state['values']['options']['expose']['required'])) {
      // Who cares what the value is if it's exposed and non-required.
      return;
    }

    $this->validateValidTime($form['value'], $form_state['values']['options']['operator'], $form_state['values']['options']['value']);
  }

  /**
   * Validate that the time values convert to something usable.
   */
  public function validateValidTime(&$form, FormStateInterface $form_state, $operator, $value) {
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
   * Overrides opBetween().
   */
  protected function opBetween($field) {
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
   * Overrides opSimple().
   */
  protected function opSimple($field) {
    $value = format_date(strtotime($this->value['value']), 'odata_datetime_format');

    $this->query->addWhere($this->options['group'], $this->realField, "datetime'$value'", "+$this->operator+");
  }

}
