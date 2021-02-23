#!/usr/bin/bash
php composer.phar update --prefer-dist;
php composer.phar dump-autoload --optimize;