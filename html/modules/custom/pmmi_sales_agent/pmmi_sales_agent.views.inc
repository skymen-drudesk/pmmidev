<?php

/**
 * @file
 * Provide views data for pmmi_sales_agent module.
 */

/**
 * Implements hook_views_data_alter().
 */
function pmmi_sales_agent_views_data_alter(&$data) {
  $data['views']['downloads_favorites_button'] = array(
    'title' => t('Downloads favorites button'),
    'help' => t('Displays the download favorites button.'),
    'area' => array(
      'id' => 'downloads_favorites_button',
    ),
  );
}
