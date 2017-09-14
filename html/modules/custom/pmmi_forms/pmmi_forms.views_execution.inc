<?php

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Alter search_page views query for changing Exposed filter.
 *
 * Implements hook_views_query_alter().
 */
function pmmi_forms_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  if ($view->id() == 'search_page' && $view->current_display == 'block_1') {
    if (!$query_params = \Drupal::request()->query->all()) {
      // There are no available filters.
      return;
    }

    // If selected `Pages` in exposed filter - add to results PagaManagerSearch
    // entities too.
    $where = $view->query->getWhere();
    $group_id = max(array_keys($where));

    if ($query_params['page_types']['pages'] === 'pages') {
      $view->query->setWhereGroup('OR', ++$group_id);
      $view->query->addCondition('content', '', '<>', $group_id);
    }
  }
}
