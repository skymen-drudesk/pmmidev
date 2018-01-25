#!/usr/bin/env bash

dockercompose="-f docker-compose.yml -f docker-compose.override.yml -f docker-compose.osx.yml"

docker-compose stop
docker-compose ${dockercompose} up -d --build
docker-sync start
