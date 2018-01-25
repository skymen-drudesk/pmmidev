#!/usr/bin/env bash

set -e
path="$(dirname "$0")"
pushd $path/../..
base="$(pwd)";

if [[ -f "$base/.env" ]]; then
  echo "Using Custom ENV file at $base/.env"
  source "$base/.env"
elif [[ -f "$base/env.dist" ]]; then
  echo "Using Project's Distributed ENV file at $base/env.dist"
  source "$base/env.dist"
else
  echo "Using dd Party's ENV file at dd-party.env"
  source "dd-party.env"
fi

# Set variables for Drupal related directories.
drupal_root=${DRUPAL_ROOT}
theme_base=${THEME_ROOT}
