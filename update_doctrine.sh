#!/bin/bash

php composer.phar dump-autoload --optimize;

php artisan config:cache
php artisan route:cache
php artisan view:clear
php artisan view:cache

php artisan doctrine:clear:metadata:cache
php artisan doctrine:clear:query:cache
php artisan doctrine:clear:result:cache
php artisan doctrine:generate:proxies
php artisan config:clear
