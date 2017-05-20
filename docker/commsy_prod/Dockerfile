FROM php:5.6-fpm

# install additinal packages and PHP extensions
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
        sudo \
    && docker-php-ext-install -j$(nproc) iconv mcrypt \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-install -j$(nproc) mcrypt \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) imap

# copy configurations
COPY conf/commsy.ini /usr/local/etc/php/conf.d/
COPY conf/opcache.ini /usr/local/etc/php/conf.d/
COPY conf/commsy.pool.conf /usr/local/etc/php-fpm.d/

# Install Node.js
RUN curl -sL https://deb.nodesource.com/setup_7.x | bash -
RUN apt-get install -yqq nodejs

# Install global Node.js packages
RUN npm -g install bower gulp

VOLUME /var/www/html

ENV COMMSY_COMMIT_SHA 1bcd46e53c0bf0aab421aa1a364039c3c82fe73f

RUN curl -o commsy.zip -SL https://github.com/commsy/commsy/archive/${COMMSY_COMMIT_SHA}.zip \
        && unzip commsy.zip -d /usr/src/commsy \
        && rm commsy.zip \
        && mv /usr/src/commsy/commsy-${COMMSY_COMMIT_SHA}/* /usr/src/commsy/ \
        && rm -r /usr/src/commsy/commsy-${COMMSY_COMMIT_SHA} \
        && chown -R www-data:www-data /usr/src/commsy

#COPY docker-entrypoint.sh /entrypoint.sh
#RUN chmod u+x /entrypoint.sh

#ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]