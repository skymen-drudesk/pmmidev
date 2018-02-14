<?php

namespace Drupal\datetime_range\Tests\Views;

use Drupal\datetime\Tests\Views\DateTimeHandlerTestBase;

/**
 * Base class for testing datetime handlers.
 */
abstract class DateRangeHandlerTestBase extends DateTimeHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['datetime_test', 'node', 'datetime_range'];

  /**
   * Type of the field.
   *
   * @var string
   */
  protected static $field_type = 'daterange';

}
