#!/bin/bash
set -e
export DOCKER_SCAN_SUGGEST=false

docker compose stop
docker compose rm
docker compose up -d --build --force-recreate