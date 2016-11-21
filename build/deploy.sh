#!/bin/bash

# Usage info
usage() {
cat << EOF
Usage: ${0##*/} [-hbi]

    -h  Display this help and exit.
    -b  Perform Docker Compose build.
    -i  Perform Drupal install.

Note that order matters.

The default will start the containers and update Drupal to match the code.

EOF
}

while getopts ":hbi" opt; do
  case ${opt} in
    h )
      usage
      exit
      ;;
    b )
      docker-compose --file docker-compose.production.yml up -d --build
      docker exec -it profood_tech_web service rsyslog start
      docker exec -it profood_tech_web ./build/sendmail_config.sh
      docker exec -it profood_tech_web service sendmail start
      docker exec -it profood_tech_web service apache2 graceful
      docker exec -it profood_tech_web  ./build/update.sh
      if [ "$?" != "0" ]; then
        echo "Uh oh. The install failed. You should check the output above for errors."
        exit 1
      fi
      ;;
    i )
      docker-compose up -d --file docker-compose.production.yml
      docker exec -it profood_tech_web  ./build/install.sh
      if [ "$?" != "0" ]; then
        echo "Uh oh. The install failed. You should check the output above for errors."
        exit 1
      fi
      ;;
    \? )
      docker-compose up -d --file docker-compose.production.yml
      docker exec -it profood_tech_web  ./build/update.sh
      if [ "$?" != "0" ]; then
        echo "Uh oh. The install failed. You should check the output above for errors."
        exit 1
      fi
      ;;
  esac
done

if [ $OPTIND -eq 1 ]; then
  docker-compose up -d --file docker-compose.production.yml
  docker exec -it profood_tech_web /bin/bash ./build/update.sh
  if [ "$?" != "0" ]; then
	echo "Uh oh. The update failed. You should check the output above for errors."
	exit 1
  fi
fi

shift $((OPTIND-1))

if [ "$?" = "0" ]; then
  echo "Setup complete. Now get the party started!"
fi
