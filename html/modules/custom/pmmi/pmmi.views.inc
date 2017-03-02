<?php

/**
 * Implements hook_views_data_alter().
 */
function pmmi_views_data_alter(array &$data) {
  $data['node']['node_webform_submissions'] = array(
    'title' => t('Node webform submissions'),
    'field' => array(
      'title' => t('Node webform submissions'),
      'id' => 'node_webform_submissions',
    ),
  );
}
