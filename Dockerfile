ARG PHP_VERSION=7.1
ARG NGINX_VERSION=1.19

FROM php:7.1-fpm-stretch AS commsy_php

# install additinal packages and PHP extensions
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        zlib1g-dev \
        libicu-dev \
        libc-client-dev \
        libkrb5-dev \
        libxml2-dev \
        libldap2-dev \
        g++ \
        git \
        zip \
        sudo \
        apt-transport-https \
        mariadb-client \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-install -j$(nproc) mcrypt \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) imap \
    && docker-php-ext-install -j$(nproc) soap \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install -j$(nproc) opcache \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) ldap

# Install Node.js
RUN curl -sL https://deb.nodesource.com/setup_16.x | bash -
RUN apt-get install -y nodejs

# Composer
COPY --from=composer:1 /usr/bin/composer /usr/bin/composer

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
# install Symfony Flex globally to speed up download of Composer packages (parallelized prefetching)
RUN set -eux; \
	composer global require "symfony/flex" --prefer-dist --no-progress --no-suggest --classmap-authoritative; \
	composer clear-cache
ENV PATH="${PATH}:/root/.composer/vendor/bin"

# yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
RUN apt-get update && apt-get install -y yarn

# xdebug
RUN pecl install xdebug-2.9.8 \
    && docker-php-ext-enable xdebug

RUN { \
        echo 'xdebug.remote_host=10.254.254.254'; \
        echo 'xdebug.remote_autostart=1'; \
        echo 'xdebug.idekey=PHPSTORM'; \
        echo 'xdebug.default_enabled=0'; \
        echo 'xdebug.remote_enable=1'; \
        echo 'xdebug.remote_connect_back=0'; \
    } > /usr/local/etc/php/conf.d/xdebug.ini

# wkhtmltopdf
RUN apt-get update && apt-get install -y \
        xfonts-base \
        xfonts-75dpi \
        fontconfig \
        libxrender1 \
        xvfb

RUN curl -o /usr/src/wkhtmltopdf.deb -SL https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.stretch_amd64.deb \
        && dpkg -i /usr/src/wkhtmltopdf.deb

# copy configurations
COPY docker/php/commsy.ini /usr/local/etc/php/conf.d/
COPY docker/php/commsy.pool.conf /usr/local/etc/php-fpm.d/

WORKDIR /var/www/html

# build for production
ARG APP_ENV=prod

COPY .env ./

# prevent the reinstallation of vendors at every changes in the source code
COPY composer.json composer.lock symfony.lock ./
RUN set -eux; \
	composer install --prefer-dist --no-dev --no-scripts --no-progress --no-suggest; \
	composer clear-cache

# prevent the reinstallation of node modules at every changes in the source code
COPY webpack.config.js tsconfig.json package.json yarn.lock ./
COPY assets assets/
RUN set -eux; \
	yarn install; \
	yarn build; \
	rm -r assets; \
	rm tsconfig.json

# copy only specifically what we need
COPY bin bin/
COPY config config/
COPY legacy legacy/
COPY public public/
COPY src src/
COPY templates templates/
COPY translations translations/

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync

COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

##############################################################################

FROM nginx:${NGINX_VERSION}-alpine AS commsy_nginx

COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

COPY --from=commsy_php /var/www/html/public public/

##############################################################################

FROM nginx:${NGINX_VERSION}-alpine AS commsy_test_nginx

COPY docker/nginx/conf.d/test.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

#COPY --from=commsy_php /var/www/html/public public/