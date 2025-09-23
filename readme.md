# OpenStackId Resource Server

## Prerequisites

    * LAMP/LEMP environment
    * PHP >= 7.1
    * Redis
    * composer (https://getcomposer.org/)

## Install

run following commands on root folder
   * curl -s https://getcomposer.org/installer | php
   * php composer.phar install --prefer-dist
   * php composer.phar dump-autoload --optimize
   * php artisan migrate --env=YOUR_ENVIRONMENT
   * php artisan db:seed --env=YOUR_ENVIRONMENT
   * phpunit --bootstrap vendor/autoload.php
   * php artisan doctrine:generate:proxies
   * php artisan doctrine:clear:metadata:cache
   * php artisan doctrine:clear:query:cache
   * php artisan doctrine:clear:result:cache
   * php artisan doctrine:ensure:production
   * php artisan route:clear
   * php artisan route:cache
   * give proper rights to storage folder (775 and proper users)
   * chmod 777 vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer
   
## Permissions

Laravel may require some permissions to be configured: folders within storage and vendor require write access by the web server.   

## create SS schema

php artisan doctrine:schema:create --sql --em=model > ss.sql

## validate SS schema

php artisan doctrine:schema:validate

## Doctrine Migrations

# For Config Storage

## create new migration
php artisan doctrine:migrations:generate --connection=config --create=<table-name>

## check status
php artisan doctrine:migrations:status --connection=config

## run
php artisan doctrine:migrations:migrate --connection=config

# For Model Storage

## create new migrations
php artisan doctrine:migrations:generate --connection=model --create=<table-name>

## check status
php artisan doctrine:migrations:status --connection=model

## run
php artisan doctrine:migrations:migrate --connection=model 

## Queues

php artisan queue:work

## message brokers
- php artisan queue:work message_broker
- php artisan queue:work sponsor_users_sync_message_broker

# Audit Log Management

## purge
php artisan audit:purge-log _SUMMIT_ID_ _DATE_BACKWARD_FROM_

- SUMMIT_ID: Summit id to clear audit log from
- DATE_BACKWARD_FROM: Maximum date to delete starting from the beginning

## OpenTelemetry Observability

This application includes OpenTelemetry instrumentation for distributed tracing and monitoring.

### Quick Setup
```bash
# Enable in .env
OTEL_SERVICE_ENABLED=true
OTEL_SERVICE_NAME=summit-api
OTEL_INSTRUMENTATION_GUZZLE=true

# Start collector and Elasticsearch
docker compose up -d otel-collector elasticsearch

# View traces
curl http://localhost:55679/debug/tracez
```