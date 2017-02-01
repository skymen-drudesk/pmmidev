#!/bin/bash

dockercompose=docker-compose-osx.yml

docker-compose stop
docker-compose --file $dockercompose up -d --build
docker-sync start
