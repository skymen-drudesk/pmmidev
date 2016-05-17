#!/bin/bash

# Usage info
usage() {
cat << EOF
Usage: ${0##*/} [-hb]

    -h  Display this help and exit.
    -b  Perform Docker Compose build and Drupal install.
EOF
}

while getopts ":hb" opt; do
  case ${opt} in
    h )
      usage
      exit
      ;;
    b )
      # Need to do composer install to make sure all files/packages in place.
      composer install
      docker-compose up -d --build
      docker exec -it pmmiweb  ./build/install.sh
      if [ "$?" != "0" ]; then
        echo "Uh oh. The install failed. You should check the output above for errors."
        exit 1
      fi
      ;;
    \? )
      usage
      ;;
  esac
done

if [ $OPTIND -eq 1 ]; then
  # Need to do composer install to make sure all files/packages in place.
  composer install
  docker-compose up -d
  docker exec -it pmmiweb /bin/bash ./build/update.sh
  if [ "$?" != "0" ]; then
	echo "Uh oh. The update failed. You should check the output above for errors."
	exit 1
  fi
fi

shift $((OPTIND-1))

if [ "$?" = "0" ]; then
  echo "Setup complete. Now get the party started!"
fi
