#!/bin/bash

php artisan doctrine:migrations:migrate --em=config
php artisan doctrine:migrations:migrate --em=model