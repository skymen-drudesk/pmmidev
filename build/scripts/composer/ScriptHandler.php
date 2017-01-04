<?php

/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */

namespace DrupalProject\composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler {

  protected static function getDrupalRoot($project_root) {
    return $project_root .  '/html';
  }

  public static function buildScaffold(Event $event) {
    $fs = new Filesystem();
    if (!$fs->exists(static::getDrupalRoot(getcwd()) . '/autoload.php')) {
      \DrupalComposer\DrupalScaffold\Plugin::scaffold($event);
    }
  }

  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $root = getcwd();
    $drupal_root = static::getDrupalRoot(getcwd());

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    // Required for unit testing
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupal_root . '/'. $dir)) {
        $fs->mkdir($drupal_root . '/'. $dir);
        $fs->touch($drupal_root . '/'. $dir . '/.gitkeep');
      }
    }

    // Prepare the settings file for installation
    if (!$fs->exists($drupal_root . '/sites/default/settings.php')) {
      $fs->copy('cnf/settings.php', $drupal_root . '/sites/default/settings.php');
      $fs->chmod($drupal_root . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Create a sites/default/settings.php file with chmod 0666");
    }

    // Prepare the local settings file for installation
    if (!$fs->exists($drupal_root . '/sites/default/settings.local.php')) {
      $fs->copy('cnf/settings.local.php', $drupal_root . '/sites/default/settings.local.php');
      $fs->chmod($drupal_root . '/sites/default/settings.local.php', 0666);
      $event->getIO()->write("Create a sites/default/settings.local.php file with chmod 0666");
    }

    // Prepare the services file for installation
    if (!$fs->exists($drupal_root . '/sites/default/services.yml')) {
      $fs->copy($drupal_root . '/sites/default/default.services.yml', $drupal_root . '/sites/default/services.yml');
      $fs->chmod($drupal_root . '/sites/default/services.yml', 0666);
      $event->getIO()->write("Create a sites/default/services.yml file with chmod 0666");
    }

    // Create the files directory with chmod 0770
    if (!$fs->exists($drupal_root . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupal_root . '/sites/default/files', 0770);
      umask($oldmask);
      $event->getIO()->write("Create a sites/default/files directory with chmod 0770");
    }

    // Create the private files directory with chmod 0770
    if (!$fs->exists($root . '/private')) {
      $oldmask = umask(0);
      $fs->mkdir($root . '/private', 0770);
      $fs->chown($root . '/private', 'www-data');
      $fs->chgrp($root . '/private', 'www-data');
      umask($oldmask);
      $event->getIO()->write("Create a ../private directory with chmod 0770");
    }

    // Copy .htaccess to private files directory
    if (!$fs->exists($root . '/private/.htaccess')) {
      $fs->copy('cnf/private.htaccess', $root . '/private/.htaccess');
      $fs->chmod($root . '/private/.htaccess', 0644);
      $fs->chown($root . '/private/.htaccess', 'www-data');
      $fs->chgrp($root . '/private/.htaccess', 'www-data');
      $event->getIO()->write("Created .htaccess in private files directory");
    }
  }

}
