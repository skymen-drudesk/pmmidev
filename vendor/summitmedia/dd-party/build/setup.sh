#!/usr/bin/env bash

set -e
project=$1
path=$(dirname "$0")
base=$(cd $path/.. && pwd)

[[ ! -z "$(grep 'PROJECT="default"' project.env.dist)" ]] && sed -i "s/default/$project/" project.env.dist

if [[ ! -f project.env ]]
then
  echo "Creating Environment File"
  echo "source project.env.dist" > project.env
fi
source project.env
if [[ -d html/modules/custom/default ]]
then
  echo "Setting up Default Project Modules."
  if [[ ! -d html/modules/custom/$project ]]
  then
    mkdir html/modules/custom/$project
  fi
  mv html/modules/custom/default/default.module html/modules/custom/$project/$project.module
  mv html/modules/custom/default/default.info.yml html/modules/custom/$project/$project.info.yml
  rm -r html/modules/custom/default
  sed -i s/default/$project/g html/modules/custom/$project/$project.* ./composer.json
  echo "*****************************************"
  echo "* Don't forget to Commit these changes. *"
  echo "*****************************************"
fi

if [[ ! -z `grep "# Promet Drupal 8 Framework" README.md` ]]
then
  sed -i "1s/^# Promet Drupal 8 Framework/# $project/" README.md
  sed -i "s/drupalproject/$project/" README.md
fi
