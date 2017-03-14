<?php

/**
 * @file
 * Provide views data for pmmi_sales_agent module.
 */

/**
 * Implements hook_views_data_alter().
 */
function pmmi_sales_agent_views_data_alter(&$data) {
  $data['views']['downloads_favorites_button'] = [
    'title' => t('Downloads favorites button'),
    'help' => t('Displays the download favorites button.'),
    'area' => [
      'id' => 'downloads_favorites_button',
    ],
  ];

  // Describe specific field for the 'created' property, which allows us to get
  // transformed date (i.e. using FROM_UNIXTIME mysql function).
  $data['sad_user_stat']['created_from_unixtime'] = [
    'title' => t('Created (from unixtime)'),
    'help' => t('Created date (from unixtime) in the next format: %M %Y. Use for MySql only.'),
    'real field' => 'created',
    'field' => [
      'id' => 'created_from_unixtime',
    ],
  ];
}
