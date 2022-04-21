#!/usr/bin/bash

php artisan doctrine:migrations:migrate --connection=config
php artisan doctrine:migrations:migrate --connection=model