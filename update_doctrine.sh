#!/usr/bin/bash

composer dump-autoload --optimize;

php artisan config:cache
php artisan route:cache
php artisan view:clear
php artisan view:cache

php artisan doctrine:clear:metadata:cache --no-interaction --em=model
php artisan doctrine:clear:metadata:cache --no-interaction --em=config
php artisan doctrine:clear:query:cache --em=model
php artisan doctrine:clear:query:cache --em=config
php artisan doctrine:clear:result:cache --no-interaction --em=model
php artisan doctrine:clear:result:cache --no-interaction --em=config
php artisan doctrine:generate:proxies --no-interaction --em=model
php artisan doctrine:generate:proxies --no-interaction --em=config
php artisan config:clear
