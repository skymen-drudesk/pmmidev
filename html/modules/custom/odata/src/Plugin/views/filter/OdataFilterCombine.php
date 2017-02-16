<?php

namespace Drupal\odata\Plugin\views\filter;

/**
 * @file
 * Definition of ODataHandlerFilterCombine.
 */

/**
 * Filter handler which allows to search on multiple fields.
 *
 * Extends views_handler_filter_combine.
 */
class OdataFilterCombine extends views_handler_filter_combine {

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

    $this->view->_build('field');
    $fields = array();
    // Only add the fields if they have a proper field and table alias.
    foreach ($this->options['fields'] as $id) {
      $field = $this->view->field[$id];
      // Always add the table of the selected fields to be sure a table alias
      // exists.
      $field->ensure_my_table();
      if (!empty($field->field_alias) && !empty($field->field_alias)) {
        $fields[] = "$field->real_field";
      }
    }

    if ($fields) {
      foreach ($fields as $key => $field) {
        $separated_fields[] = $field;
      }
      // Ensure that value is encoded properly.
      $this->value = drupal_encode_path($this->value);

      $info = $this->operators();

      if (!empty($info[$this->operator]['method'])) {
        $this->{$info[$this->operator]['method']}($separated_fields);
      }
    }
  }

  /**
   * Overrides op_equal().
   */
  public function op_equal($fields) {

    foreach ($fields as $field) {
      $expression[] = "$field+$this->operator+'$this->value'";
    }
    $this->query->addWhereExpression($this->options['group'], "(" . implode("+or+", $expression) . ")", NULL);
  }

  /**
   * Overrides op_contains().
   */
  public function op_contains($fields) {

    foreach ($fields as $field) {
      $expression[] = "substringof('$this->value',$field)+eq+true";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+or+", $expression) . ")", NULL);
  }

  /**
   * Overrides op_contains().
   */
  public function op_not($fields) {

    foreach ($fields as $field) {
      $expression[] = "substringof('$this->value',$field)+eq+false";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+or+", $expression) . ")", NULL);
  }

  /**
   * Overrides op_starts().
   */
  public function op_starts($fields) {

    foreach ($fields as $field) {
      $expression[] = "startswith($field,'$this->value')+eq+true";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+or+", $expression) . ")", NULL);
  }

  /**
   * Overrides op_starts().
   */
  public function op_not_starts($fields) {

    foreach ($fields as $field) {
      $expression[] = "startswith($field,'$this->value')+eq+false";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides op_ends().
   */
  public function op_ends($fields) {
    foreach ($fields as $field) {
      $expression[] = "endswith($field,'$this->value')+eq+true";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides op_not_ends().
   */
  public function op_not_ends($fields) {
    foreach ($fields as $field) {
      $expression[] = "endswith($field,'$this->value')+eq+false";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides op_length().
   */
  public function op_length($fields) {
    foreach ($fields as $field) {
      $expression[] = "length($field)+eq+$this->value";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides op_not_length().
   */
  public function op_not_length($fields) {
    foreach ($fields as $field) {
      $expression[] = "length($field)+ne+$this->value";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides op_shorter().
   */
  public function op_shorter($fields) {
    foreach ($fields as $field) {
      $expression[] = "length($field)+lt+$this->value";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides op_longer().
   */
  public function op_longer($fields) {
    foreach ($fields as $field) {
      $expression[] = "length($field)+gt+$this->value";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }
}
