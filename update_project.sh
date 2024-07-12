#!/bin/bash

composer update --prefer-dist;
composer dump-autoload --optimize;