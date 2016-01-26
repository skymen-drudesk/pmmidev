#!/bin/bash
set -e
path=$(dirname "$0")
true=`which true`
source $path/common.sh

echo "Installing Drupal minimal profile."
echo "Installing site...";
sqlfile=$base/build/ref/$PROJECT.sql
gzipped_sqlfile=$sqlfile.gz
if [ -e "$gzipped_sqlfile" ]; then
  echo "...from reference database."
  $drush sql-drop -y
  zcat "$gzipped_sqlfile" | $drush sqlc
elif [ -e "$sqlfile" ]; then
  echo "...from reference database."
  $drush sql-drop -y
  $drush sqlc < $sqlfile
else
  echo "...from scratch, with Drupal minimal profile.";
  #$drush si ${DRUPAL_PROFILE} --yes \
    #--account-name=${DRUPAL_USER} \
    #--account-pass=${DRUPAL_PASS} \
    #--site-name=${DRUPAL_SITE_NAME} \
    #--db-url=${DRUPAL_DBURL} \
    #--db-su=${MYSQL_USER} \
    #--db-su-pw=${MYSQL_PASSWORD}
  $drush sql-drop --yes
  $drush si minimal --yes \
    --account-name=admin \
    --account-pass=admin \
    --site-name=pmmi
    #--db-url=mysql://drupal:drupal@pmmi-mysql:3306/drupal \
    #--db-su=drupal \
    #do--db-su-pw=drupal

  if [[ -e "$base/cnf/drupal/system.site.yml" ]]; then
    # Without this, our import would fail in update.sh. See https://github.com/drush-ops/drush/pull/1635
    site_uuid="$(grep "uuid: " "$base/cnf/drupal/system.site.yml" | sed "s/uuid: //")"
    $drush cset system.site uuid $site_uuid -y
  fi
fi
source $path/update.sh
