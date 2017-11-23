FROM mediawiki:1.29.1

RUN apt-get update && apt-get install -y \
        libxml2-dev \
    && docker-php-ext-install soap

COPY conf/LocalSettings.php /var/www/html/LocalSettings.php

COPY plugins/mediawiki_extension-master-3ae7067eb2c04a107d502d91b696b0f563a297d5.tar.gz /var/www/html/extensions/commsy_extension.tar.gz
RUN cd /var/www/html/extensions/ \
    && tar xzf commsy_extension.tar.gz \
    && mv mediawiki_extension-master-3ae7067eb2c04a107d502d91b696b0f563a297d5 CommSy

COPY plugins/OAuth-REL1_29-4f9fe7e.tar.gz /var/www/html/extensions/OAuth-REL1_29-4f9fe7e.tar.gz
RUN cd /var/www/html/extensions/ \
    && tar xzf OAuth-REL1_29-4f9fe7e.tar.gz \
    && mv OAuth-REL1_29-4f9fe7e.tar.gz OAuth