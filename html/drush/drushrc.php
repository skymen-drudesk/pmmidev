<?php
/**
 * @file
 */

$ignore_modules = [
  'config_devel',
  'config_read_only',
  'config_update_ui',
  'dblog',
  'devel',
  'devel_debug_log',
  'field_ui',
  'page_manager',
  'stage_file_proxy',
  'syslog',
  'views_ui',
  'yamlform',
];

$command_specific['config-export']['skip-modules'] = $ignore_modules;
$command_specific['config-import']['skip-modules'] = $ignore_modules;
