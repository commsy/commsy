FROM php:7.1-fpm

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
        libxml2-dev \
        libldap2-dev \
        g++ \
        git \
        zip \
        sudo \
        apt-transport-https \
    && docker-php-ext-install -j$(nproc) iconv mcrypt \
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
RUN curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
RUN apt-get install -yqq nodejs

# yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
RUN apt-get update && apt-get install -y yarn

# install gulp
RUN yarn global add gulp

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN { \
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
    } > /usr/local/etc/php/conf.d/xdebug.ini

# copy configurations
COPY conf/commsy.ini /usr/local/etc/php/conf.d/
COPY conf/commsy.pool.conf /usr/local/etc/php-fpm.d/

# wkhtmltopdf
RUN apt-get update && apt-get install -y \
        xfonts-base \
        xfonts-75dpi \
        fontconfig \
        libxrender1 \
        xvfb

RUN curl -o /usr/src/wkhtmltopdf.deb -SL https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.2.1/wkhtmltox-0.12.2.1_linux-jessie-amd64.deb \
        && dpkg -i /usr/src/wkhtmltopdf.deb

CMD ["php-fpm"]