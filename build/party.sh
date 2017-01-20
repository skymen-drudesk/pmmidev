#!/bin/bash

set -e
path="$(dirname "$0")"
pushd $path/..
base="$(pwd)";

# Set defaults
build=
install=
dockercompose=docker-compose.production.yml
webcontainer=pmmi_prod_web

# Get environment variables.
if [[ -f "$base/.env" ]]; then
  echo "Using Custom ENV file at $base/.env"
  source "$base/.env"
else
  echo "Using Distributed ENV file at $base/env.dist"
  source "$base/env.dist"
fi

# Usage info
usage() {
cat << EOF
Usage: ${0##*/} [[[-i] [-b]] | [-h]]

    -b | --build        Perform Docker Compose build.
    -i | --install      Perform Drupal install.
    -h | --help         Display this help and exit.

The default will start the containers and update Drupal to match the
code on the local environment.

EOF
}

while [ "$1" != "" ]; do
  case $1 in
    -b | --build )
      build=1
      ;;
    -i | --install )
      install=1
      ;;
    -h | --help )
      usage
      exit 1
      ;;
  esac
  shift
done

# Set Docker Compose file based on environment.
if [ "$SITE_ENVIRONMENT" = "test" ]; then
  dockercompose=docker-compose.test.yml
  webcontainer=pmmi_test_web
elif [ "$SITE_ENVIRONMENT" = "dev" ]; then
  dockercompose=docker-compose.yml
  webcontainer=pmmi_dev_web
elif [ "$SITE_ENVIRONMENT" = "dev-osx" ]; then
  dockercompose=docker-compose-osx.yml
  webcontainer=pmmi_dev_web_osx
fi

echo "Running on the $SITE_ENVIRONMENT environment using the $dockercompose Docker Compose file."

if [ "$build" = "1" ]; then
  echo "The Docker containers will be (re)built."
fi

if [ "$install" = "1" ]; then
  echo "Drupal will be installed."
fi

echo -e "Do you wish to proceed (y/n): \c "
read  confirm

if [ "$confirm" != "y" ]; then
  exit 1
fi

if [ "$build" = "1" ]; then
  echo "Starting and building Docker containters."
  docker-compose --file $dockercompose up -d --build
  docker exec -it $webcontainer service rsyslog start
  docker exec -it $webcontainer ./build/sendmail_config.sh
  docker exec -it $webcontainer service sendmail start
  docker exec -it $webcontainer service apache2 graceful
else
  echo "Starting Docker containers."
  docker-compose --file $dockercompose up -d
fi

if [ "$?" != "0" ]; then
  echo "Uh oh. The Docker build failed. You should check the output above for errors."
  exit 1
fi

if [ "$install" = "1" ]; then
  echo "Initiating Drupal install."
  docker exec -it $webcontainer ./build/install.sh
  if [ "$?" != "0" ]; then
    echo "Uh oh. The Drupal install failed. You should check the output above for errors."
    exit 1
  fi
else
  echo "Initiating updates."
  docker exec -it $webcontainer ./build/update.sh
fi

if [ "$?" = "0" ]; then
  echo "Success! Now get the party started!"
else
  echo "Uh oh. The party failed to get started. You should check the output above for errors."
fi
