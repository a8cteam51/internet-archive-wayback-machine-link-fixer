#!/bin/bash
CONTAINER_ID="$(docker ps | grep tests-wordpress  | awk '{print $1}')"
docker exec $CONTAINER_ID docker-php-ext-install pdo_mysql
docker exec $CONTAINER_ID service apache2 reload
