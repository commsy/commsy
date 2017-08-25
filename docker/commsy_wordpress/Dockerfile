FROM php:5.6-fpm

# install additinal packages and PHP extensions
RUN apt-get update && apt-get install -y \
        libxml2-dev \
    && docker-php-ext-install -j$(nproc) soap

CMD ["php-fpm"]