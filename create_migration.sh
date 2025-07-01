#!/bin/bash

php artisan doctrine:migrations:generate --em=model
cd database/migrations/model && chown 1000:1000 * && chmod 664 *