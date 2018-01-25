#!/usr/bin/env bash

set -e
path="$(dirname "$0")"
source "$path/common.sh"

# Change to the Drupal Directory Just In Case
pushd "$drupal_root"
echo "drupal base: $drupal_root"
echo "drush in update.sh: $drush"

modules_enabled="$($drush pm-list --pipe --type=module --status=enabled --no-core)"
if [[ ${modules_enabled} == *"features"* ]]; then
  features_enabled=1
else
  features_enabled=0
fi
echo "features enabled: $features_enabled"

# This was added because of upgrades like Rules 2.8 to 2.9 and Feeds alpha-9 to beta-1 where
# new code and database tables are added and running other code will cause white screen until
# the updates are run.
echo "Initial Update so updated modules can work.";
$drush updb -y;
if [ "$DRUPAL_VERSION" = 8 ]; then
  echo "Updating entities."
  $drush entup -y
fi
# Rebuild cache so recently added modules are found.
echo "Clearing cache.";
$drush $drush_cache_clear all
echo "Enabling modules.";
$drush en $(echo $DROPSHIP_SEEDS | tr ':' ' ') -y
echo "Enabling themes.";
$drush en $DEFAULT_THEME $ADMIN_THEME -y
echo "Clearing drush cache."
$drush $drush_cache_clear drush
if [ "$DRUPAL_VERSION" = 8 ]; then
  echo "Reverting configuration."
  $drush cim --partial -y
fi
{
  features_ignore_enabled_test="$($drush help fic)"
  if [[ ${features_ignore_enabled_test} == *"Aliases: fic"* ]]; then
    features_ignore_enabled=1
  else
    features_ignore_enabled=0
  fi
  echo "features ignore enabled: $features_ignore_enabled"
} || {}
if [ "$features_enabled" = 1 ]; then
  echo "Importing Features"
  $drush fra -y
fi
if [ -e "$base/config/drupal/overrides" ]; then
  echo "Importing overrides configuration."
  $drush cim overrides --partial -y
fi
echo "Clearing caches one last time.";
$drush $drush_cache_clear all

chmod -R +w "$base/cnf"
chmod -R +w "$drupal_root/sites/default"
