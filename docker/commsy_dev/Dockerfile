FROM php:5.6-fpm

# install PHP extensions
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
        zlib1g-dev \
        libicu-dev \
        libc-client-dev \
        libkrb5-dev \
        g++ \
        git \
        zip \
    && docker-php-ext-install -j$(nproc) iconv mcrypt \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-install -j$(nproc) mcrypt \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) imap
#    && docker-php-ext-install -j$(nproc) opcache

RUN pecl install xdebug-2.5.0 \
    && docker-php-ext-enable xdebug

# copy configurations
COPY conf/commsy.ini /usr/local/etc/php/conf.d/
COPY conf/commsy.pool.conf /usr/local/etc/php-fpm.d/

# Install Node.js
RUN curl -sL https://deb.nodesource.com/setup_6.x | bash -
RUN apt-get install nodejs -yqq

# Install global Node.js packages
RUN npm -g install bower gulp