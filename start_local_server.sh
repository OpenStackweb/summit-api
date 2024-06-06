#!/bin/bash
set -e
export DOCKER_SCAN_SUGGEST=false

docker compose run --rm app composer install --ignore-platform-req=ext-gd --ignore-platform-req=ext-imagick
docker compose run --rm app php artisan db:create_test_db --schema=config
docker compose run --rm app php artisan db:create_test_db --schema=model
docker compose run --rm app php artisan db:seed --force
docker compose run --rm app php artisan db:seed_test_data
docker compose up -d
docker compose exec app /bin/bash