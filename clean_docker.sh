#!/bin/bash

set -e

docker kill $(docker ps -q)
docker rm $(docker ps -a -q)
docker rmi $(docker images -q) -f