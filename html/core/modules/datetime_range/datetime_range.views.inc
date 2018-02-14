<?php

/**
 * @file
 * Provides views data for the datetime_range module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function datetime_range_field_views_data(FieldStorageConfigInterface $field_storage) {
  \Drupal::service('module_handler')->loadInclude('datetime', 'inc', 'datetime.views');
  $data = datetime_type_field_views_data($field_storage);
  $data = datetime_type_field_views_data($field_storage, $data, 'end_value');

  return $data;
}
