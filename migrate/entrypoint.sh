#!/bin/sh
set -e

exec /migrate -path /migrations/ -database "mysql://${MYSQL_USER}:${MYSQL_PASSWORD}@(${MYSQL_HOSTNAME}:3306)/${MYSQL_DATABASE}" up
