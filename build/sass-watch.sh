#!/bin/bash

# Get the container ID of the web service
CONTAINER=$(docker-compose ps -q web)

docker exec -it ${CONTAINER} /bin/bash -c "cd ./html/themes/contrib/show_sites_core && npm run sass:w"