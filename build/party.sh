#!/bin/sh

composer install
docker-compose up -d --build
docker exec pmmiweb ./build/setup.sh
docker exec pmmiweb ./build/install.sh
