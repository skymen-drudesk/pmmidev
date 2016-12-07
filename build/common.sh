#!/bin/bash

set -e
path="$(dirname "$0")"
pushd $path/..
base="$(pwd)";

drupal_base="$base/html"
theme_base="$base/html/themes/custom/pmmi_bootstrap"

while getopts ":r:d:" opt; do
  case $opt in
    r)
      # Drupal Root Directory
      drupal_base="$base/$OPTARG";
      ;;
    d)
      # Drush Command
      drush="$OPTARG";
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      echo "
Usage: install.sh [-r DRUPAL_ROOT] [-d \"DRUSH COMMAND\"]
 Examples:
 `basename $0`                     # Installs in 'html' using 'drush -r html'
                                    or 'vendor/bin/drush -y -r html'

 `basename $0` -r docroot          # Installs in 'docroot' using
                                    'drush -r docroot' or
                                    'vendor/bin/drush -y -r html'

 `basename $0` -d \"drush @local\"   # Sets the drush command to what you want

 "
        exit 1;
      ;;
  esac
done

# Set the Default (or custom) drush commaand.
if [[ "$drush" == "" ]]; then
  echo "defining drush"
  if which drush > /dev/null && [[ $(echo "$(drush --version --pipe) >= 8.0" | bc) == 1 ]]; then
    drush="drush -r $drupal_base"
  else
    drush="$base/vendor/bin/drush -r $drupal_base"
  fi
fi
echo "DRUSH: $drush"
echo "DRUPAL BASE: $drupal_base"

# Set the Default (or custom) drupal (console) command.
if [[ "$drupal" == "" ]]; then
  if which drupal > /dev/null && [[ $(echo "$(drupal --version --pipe) >= 1.0.0-beta5" | bc) == 1 ]]; then
    drupal="drupal --root=$drupal_base $@"
  else
    drupal="$base/vendor/bin/drupal --root=$drupal_base $@"
  fi
fi

if [[ -f "$base/.env" ]]; then
  echo "Using Custom ENV file at $base/.env"
  source "$base/.env"
else
  echo "Using Distributed ENV file at $base/env.dist"
  source "$base/env.dist"
fi

# If Composer.json exists and the composer command exist.
if [[ -e "$base/composer.json" ]] && which composer > /dev/null; then
  # Then run Composer Install
  echo "Installing dependencies with Composer.";
  composer install
fi

# If package.json for pmmi_bootstrap exists and the npm command exists.
if [[ -e "$theme_base/package.json" ]] && which npm > /dev/null; then
  # Then run npm install
  echo "Installing packages for custom theme with npm.";
  npm install --prefix $theme_base
fi

# If bower.json for pmmi_bootstrap exists and the bower command exists.
if [[ -e "$theme_base/bower.json" ]] && which bower > /dev/null; then
  # Then run bower install
  echo "Installing packages for custom theme with bower.";
  bower install --prefix $theme_base
fi
