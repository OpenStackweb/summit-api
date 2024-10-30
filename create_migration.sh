#!/bin/bash

php artisan doctrine:migrations:generate --connection=model
cd database/migrations/model && chown 1000:1000 * && chmod 664 *