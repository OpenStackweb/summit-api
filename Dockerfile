FROM php:8.3-fpm

ARG DEBIAN_FRONTEND=noninteractive
ARG GITHUB_OAUTH_TOKEN
ARG XDEBUG_VERSION="xdebug-3.3.2"

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV GITHUB_OAUTH_TOKEN=$GITHUB_OAUTH_TOKEN
ENV PHP_DIR /usr/local/etc/php

ARG NVM_VERSION="v0.39.7"
ARG NODE_VERSION="18.20.2"
# base packages
RUN apt-get update
RUN apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libssl-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    redis-tools \
    nano \
    python3 \
    make \
    g++\
    gpg \
    gettext \
    libmagickwand-dev

# node / npm
RUN apt install -y nodejs npm

# nvm
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/$NVM_VERSION/install.sh | bash
RUN  \. ~/.nvm/nvm.sh && nvm install $NODE_VERSION

# yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN  apt update && apt install -y yarn

ENV NVM_DIR=/root/.nvm

RUN apt clean && rm -rf /var/lib/apt/lists/*

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions bcmath exif gettext gd imagick mbstring openssl pcntl pdo pdo_mysql sockets ${XDEBUG_VERSION} zip

# XDEBUG
COPY docker-compose/php/docker-php-ext-xdebug.ini $PHP_DIR/conf.d/docker-php-ext-xdebug.ini

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN echo 'memory_limit = 1024M' >> $PHP_INI_DIR/php.ini;

WORKDIR /var/www
COPY . /var/www
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer config -g github-oauth.github.com $GITHUB_OAUTH_TOKEN
RUN chmod 777 -R storage