FROM wordpress

# install additinal packages and PHP extensions
RUN apt-get update && apt-get install -y libxml2-dev && docker-php-ext-install -j$(nproc) soap