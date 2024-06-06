#!/bin/bash
set -e
export DOCKER_SCAN_SUGGEST=false

docker compose run --rm app composer install --ignore-platform-req=ext-gd --ignore-platform-req=ext-imagick
docker compose run --rm app php artisan doctrine:migrations:migrate --em=model --no-interaction
docker compose run --rm app php artisan doctrine:migrations:migrate --em=config --no-interaction
docker compose run --rm app php artisan db:seed --force
docker compose up -d
docker compose exec app /bin/bash