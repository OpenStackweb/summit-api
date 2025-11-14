#!/bin/bash
set -e
export DOCKER_SCAN_SUGGEST=false

# Start only the app container first
docker compose up -d app
# Run all setup commands inside the running container
docker compose exec app composer install
docker compose exec app composer dump-autoload -o
docker compose exec app php artisan db:create_test_db --schema=config
docker compose exec app php artisan db:create_test_db --schema=model
docker compose exec app php artisan doctrine:migrations:migrate --no-interaction --em=config
docker compose exec app php artisan doctrine:migrations:migrate --no-interaction --em=model_write
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan db:seed_test_data
docker compose exec app php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
docker compose exec app php artisan l5-swagger:generate
# Now bring up all remaining services
docker compose up -d
# Open shell as appuser
docker compose exec app /bin/bash
