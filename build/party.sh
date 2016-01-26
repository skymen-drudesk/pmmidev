#!/bin/sh

composer install
docker-compose build
docker-compose up -d
