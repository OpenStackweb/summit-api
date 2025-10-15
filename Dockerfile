FROM php:8.3-fpm

ARG DEBIAN_FRONTEND=noninteractive
ARG GITHUB_OAUTH_TOKEN
ARG XDEBUG_VERSION="xdebug-3.3.2"

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV GITHUB_OAUTH_TOKEN=$GITHUB_OAUTH_TOKEN
ENV PHP_DIR /usr/local/etc/php
ARG YARN_VERSION="1.22.22"
ARG NVM_VERSION="v0.40.3"
ARG NODE_VERSION="20.19.4"

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
    iputils-ping \
    make \
    g++\
    gpg \
    gettext \
    libmagickwand-dev



# nvm + node + yarn via corepack
ENV NVM_DIR=/root/.nvm
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/$NVM_VERSION/install.sh | bash
# Install Node, enable Corepack (Yarn)
RUN bash -lc "source $NVM_DIR/nvm.sh && nvm install $NODE_VERSION && corepack enable && corepack prepare yarn@$YARN_VERSION --activate"
RUN apt clean && rm -rf /var/lib/apt/lists/*

# Set up our PATH correctly so we don't have to long-reference npm, node, &c.
ENV NODE_PATH=$NVM_DIR/versions/node/v$NODE_VERSION/lib/node_modules
ENV PATH=$NVM_DIR/versions/node/v$NODE_VERSION/bin:$PATH

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions bcmath exif gettext gd imagick mbstring openssl pcntl pdo pdo_mysql sockets ${XDEBUG_VERSION} zip apcu redis igbinary memcached

# XDEBUG
COPY docker-compose/php/docker-php-ext-xdebug.ini $PHP_DIR/conf.d/docker-php-ext-xdebug.ini

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN echo 'memory_limit = 1024M' >> $PHP_INI_DIR/php.ini;
# Enough shared memory for metadata/query caches
RUN echo 'apc.shm_size=128M' >> $PHP_INI_DIR/php.ini;
# Enable APCu in CLI if you run warmers via artisan
RUN echo 'apc.enable_cli=1' >> $PHP_INI_DIR/php.ini;

WORKDIR /var/www
COPY . /var/www
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN git config --global --add safe.directory /var/www
RUN composer config -g github-oauth.github.com $GITHUB_OAUTH_TOKEN
RUN chmod 777 -R storage

# access to http://localhost:8002/apc.php to see APC statistics

RUN cd /var/www/public && curl -LO https://raw.githubusercontent.com/krakjoe/apcu/master/apc.php
