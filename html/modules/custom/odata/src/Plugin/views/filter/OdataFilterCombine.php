<?php

namespace Drupal\odata\Plugin\views\filter;

///**
// * @file
// * Definition of ODataHandlerFilterCombine.
// */
use Drupal\Component\Utility\UrlHelper;
use Drupal\views\Plugin\views\filter\Combine;

/**
 * Filter handler which allows to search on multiple fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("odata_filter_combine")
 */
class OdataFilterCombine extends Combine {

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

    $this->view->_build('field');
    $fields = array();
    // Only add the fields if they have a proper field and table alias.
    foreach ($this->options['fields'] as $id) {
      $field = $this->view->field[$id];
      // Always add the table of the selected fields to be sure a table alias
      // exists.
      $field->ensureMyTable();
      if (!empty($field->field_alias) && !empty($field->field_alias)) {
        $fields[] = "$field->realField";
      }
    }

    if ($fields) {
      foreach ($fields as $key => $field) {
        $separated_fields[] = $field;
      }
      // Ensure that value is encoded properly.
      $this->value = UrlHelper::encodePath($this->value);

      $info = $this->operators();

      if (!empty($info[$this->operator]['method'])) {
        $this->{$info[$this->operator]['method']}($separated_fields);
      }
    }
  }

  /**
   * Overrides opEqual().
   */
  public function opEqual($fields) {

    foreach ($fields as $field) {
      $expression[] = "$field+$this->operator+'$this->value'";
    }
    $this->query->addWhereExpression($this->options['group'], "(" . implode("+or+", $expression) . ")", NULL);
  }

  /**
   * Overrides opContains().
   */
  public function opContains($fields) {

    foreach ($fields as $field) {
      $expression[] = "substringof('$this->value',$field)+eq+true";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+or+", $expression) . ")", NULL);
  }

  /**
   * Overrides opNotLike().
   */
  public function opNotLike($fields) {

    foreach ($fields as $field) {
      $expression[] = "substringof('$this->value',$field)+eq+false";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+or+", $expression) . ")", NULL);
  }

  /**
   * Overrides opStartsWith().
   */
  public function opStartsWith($fields) {

    foreach ($fields as $field) {
      $expression[] = "startswith($field,'$this->value')+eq+true";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+or+", $expression) . ")", NULL);
  }

  /**
   * Overrides opNotStartsWith().
   */
  public function opNotStartsWith($fields) {

    foreach ($fields as $field) {
      $expression[] = "startswith($field,'$this->value')+eq+false";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides opEndsWith().
   */
  public function opEndsWith($fields) {
    foreach ($fields as $field) {
      $expression[] = "endswith($field,'$this->value')+eq+true";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides opNotEndsWith().
   */
  public function opNotEndsWith($fields) {
    foreach ($fields as $field) {
      $expression[] = "endswith($field,'$this->value')+eq+false";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides opLength().
   */
  public function opLength($fields) {
    foreach ($fields as $field) {
      $expression[] = "length($field)+eq+$this->value";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides opNotLength().
   */
  public function opNotLength($fields) {
    foreach ($fields as $field) {
      $expression[] = "length($field)+ne+$this->value";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides opShorterThan().
   */
  public function opShorterThan($fields) {
    foreach ($fields as $field) {
      $expression[] = "length($field)+lt+$this->value";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

  /**
   * Overrides opLongerThan().
   */
  public function opLongerThan($fields) {
    foreach ($fields as $field) {
      $expression[] = "length($field)+gt+$this->value";
    }

    $this->query->addWhereExpression($this->options['group'], "(" . implode("+and+", $expression) . ")", NULL);
  }

}
