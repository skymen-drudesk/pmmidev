<?php

/**
 * @file
 * Provide views data for pmmi_sales_agent.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function pmmi_sales_agent_views_data() {
  $data = [];

  $data['pmmi_sales_agent_search_stats']['table']['group'] = t('PMMI Sales Agent Search Stats');

  $data['pmmi_sales_agent_search_stats']['table']['base'] = [
    'field' => 'id',
    'title' => t('Search agent directory usage'),
    'help' => t('Contains a statistic about usage of the search agent directory.'),
  ];

  $data['pmmi_sales_agent_search_stats']['id'] = [
    'title' => t('ID'),
    'help' => t('The ID of statistic point.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['pmmi_sales_agent_search_stats']['uid'] = [
    'title' => t('UID'),
    'help' => t('The user ID of the user on which the statistic point was written.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'title' => t('User'),
      'help' => t('The user on which the statistic point was written.'),
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
    ],
  ];

  $data['pmmi_sales_agent_search_stats']['date'] = [
    'title' => t('Timestamp'),
    'help' => t('The Unix timestamp when record has been added.'),
    'field' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
  ];

  $data['pmmi_sales_agent_search_stats']['countries_searched'] = [
    'title' => t('The searched countries'),
    'help' => t('The countries which were searched.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}
