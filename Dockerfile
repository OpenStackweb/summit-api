FROM php:8.2-fpm

ARG DEBIAN_FRONTEND=noninteractive
ARG GITHUB_OAUTH_TOKEN
ARG XDEBUG_VERSION="xdebug-3.3.2"

ENV NVM_DIR /usr/local/nvm
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV GITHUB_OAUTH_TOKEN=$GITHUB_OAUTH_TOKEN
ENV PHP_DIR /usr/local/etc/php

# base packages
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    redis-tools \
    nano \
    python3 \
    make \
    g++\
    gpg \
    gettext

RUN apt clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath sockets gettext
# XDEBUG
RUN yes | pecl install ${XDEBUG_VERSION}
COPY docker-compose/php/docker-php-ext-xdebug.ini $PHP_DIR/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www
COPY . /var/www
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer config -g github-oauth.github.com $GITHUB_OAUTH_TOKEN
RUN chmod 777 -R storage