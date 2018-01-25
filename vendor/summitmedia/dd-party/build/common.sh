#!/usr/bin/env bash

base="$(pwd)";

# Get environment variables again to be safe.
if [[ -f "$base/.env" ]]; then
  source "$base/.env"
elif [[ -f "$base/env.dist" ]]; then
  source "$base/env.dist"
else
  source "$dd_party_base/dd-party.env"
fi

# Set variables for Drupal related directories.
drupal_root=${DRUPAL_ROOT}
theme_base=${THEME_ROOT}

while getopts ":r:d:" opt; do
  case $opt in
    r)
      # Drupal Root Directory
      $drupal_root="$drupal_root/$OPTARG";
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

# Use the Drush installed by Composer.
drush="$base/vendor/bin/drush -r $drupal_root"

# Set Drush clear cache command.
if [ "$DRUPAL_VERSION" = 8 ]; then
  drush_cache_clear='cr'
else
  drush_cache_clear='cc'
fi

# Set the Drupal Console installed by Composer.
drupal="$base/vendor/bin/drupal --root=$drupal_root $@"

# If Composer.json exists and the composer command.
if [[ -e "$base/composer.json" ]] && which composer > /dev/null; then
  # Then run Composer
  echo "Installing dependencies with Composer.";
  composer install --optimize-autoloader --prefer-dist
fi

# If package.json for default theme exists and the npm command exists
# install packages with npm and bower.
if [[ -e "$theme_base/package.json" ]] && which npm > /dev/null; then
  # Then run npm install
  echo "Installing packages for custom theme with npm.";
  npm install --prefix $theme_base
  # If bower.json for pmmi_bootstrap exists and the bower command exists.
  if [[ -e "$theme_base/bower.json" ]]; then
    # Then run bower install
    echo "Installing packages for custom theme with bower.";
    npm run bower --prefix $theme_base
  fi
fi

echo 'Setting correct group on webroot.'
chgrp -R www-data ${DRUPAL_ROOT}

if [ "$SITE_ENVIRONMENT" = "prod" ]; then
  echo 'Ensuring hosts entry exists for sendmail to work.'
  $base/vendor/bin/sendmail-config
  service sendmail restart
fi
