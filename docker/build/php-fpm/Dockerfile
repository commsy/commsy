FROM php:5.6-fpm

# install additinal packages and PHP extensions
RUN apt-get update && apt-get install -y \
        unzip \
        git \
        libmcrypt-dev \
        zlib1g-dev \
        libicu-dev \
	    libxml2-dev \
        g++ \
        sudo \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
        wget \
        nano \
        htop \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-install -j$(nproc) mcrypt \
    && docker-php-ext-install -j$(nproc) mysql mysqli pdo pdo_mysql \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-install -j$(nproc) soap

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN { \
        echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)"; \
        echo 'xdebug.remote_host=10.254.254.254'; \
        echo 'xdebug.remote_autostart=1'; \
        echo 'xdebug.idekey=PHPSTORM'; \
        echo 'xdebug.default_enabled=0'; \
        echo 'xdebug.remote_enable=1'; \
        echo 'xdebug.remote_connect_back=0'; \
        echo 'xdebug.profiler_enabled=1'; \
        echo 'xdebug.profiler_enable_trigger=1'; \
        echo 'xdebug.profiler_output_name=xdebug.out.%t'; \
        echo 'xdebug.profiler_output_dir=/tmp'; \
        echo 'xdebug.remote_log = /tmp/xdebug.log'; \
    } > /usr/local/etc/php/conf.d/xdebug.ini

# install phpunit
RUN wget https://phar.phpunit.de/phpunit.phar \
    && chmod +x phpunit.phar \
    && mv phpunit.phar /usr/local/bin/phpunit 

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=60'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN { \
        echo 'date.timezone = Europe/Berlin'; \
        echo 'memory_limit = -1'; \
    } > /usr/local/etc/php/conf.d/commsy.ini

VOLUME /var/www/html

CMD ["php-fpm"]
