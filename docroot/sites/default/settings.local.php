<?php

#$base_url = 'https://www.pmmi.org'

/**
 * @file
 * Local override configuration feature.
 *
 */

ini_set('display_errors',0);
ini_set('display_startup_errors',0);
error_reporting(E_ALL);

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;

/**
 * Enable access to rebuild.php.
 *
 * This setting can be enabled to allow Drupal's php and database cached
 * storage to be cleared via the rebuild.php page. Access to this page can also
 * be gained by generating a query string from rebuild_token_calculator.sh and
 * using these parameters in a request to rebuild.php.
 */
$settings['rebuild_access'] = FALSE;

/**
 * The default list of directories that will be ignored by Drupal's file API.
 *
 * By default ignore node_modules and bower_components folders to avoid issues
 * with common frontend tools and recursive scanning of directories looking for
 * extensions.
 *
 * @see file_scan_directory()
 * @see \Drupal\Core\Extension\ExtensionDiscovery::scanDirectory()
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
  //'libraries'
];

/**
 * Enable CSS and JS aggregation.
 */
#$config['system.performance']['css']['preprocess'] = TRUE;
#$config['system.performance']['js']['preprocess'] = TRUE;

$config['config_split.config_split.sync']['status'] = TRUE;
$config['config_split.config_split.panels_pages']['status'] = TRUE;
$config['config_split.config_split.test']['status']= FALSE;
$config['config_split.config_split.dev']['status']= FALSE;
$config['config_split.config_split.ignore']['status']= FALSE;

$databases['default']['default'] = array (
  'database' => 'drupal',
  'username' => 'root',
  'password' => 'rootpasswd',
  'prefix' => '',
  'host' => 'pmmidb',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
