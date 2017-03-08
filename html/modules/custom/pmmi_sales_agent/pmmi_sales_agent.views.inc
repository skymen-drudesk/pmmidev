<?php

/**
 * @file
 * Provide views data for pmmi_sales_agent module.
 */

/**
 * Implements hook_views_data_alter().
 */
function pmmi_sales_agent_views_data_alter(&$data) {
  $data['views']['downloads_favourites_button'] = array(
    'title' => t('Downloads favourites button'),
    'help' => t('Displays the download favourites button.'),
    'area' => array(
      'id' => 'downloads_favourites_button',
    ),
  );
}
