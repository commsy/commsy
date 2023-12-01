#syntax=docker/dockerfile:1.4

# Versions
FROM php:8.2-fpm-alpine AS php_upstream
FROM mlocati/php-extension-installer:2 AS php_extension_installer_upstream
FROM composer/composer:2-bin AS composer_upstream
FROM caddy:2-alpine AS caddy_upstream

# the different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target

FROM php_upstream AS commsy_php

ENV APP_ENV=prod

# php extensions installer: https://github.com/mlocati/docker-php-extension-installer
COPY --from=php_extension_installer_upstream --link /usr/bin/install-php-extensions /usr/local/bin/

# persistent / runtime deps
RUN apk add --no-cache \
		acl \
		autoconf \
		fcgi \
		file \
		fontconfig \
		gettext \
		git \
		gnu-libiconv \
		libxrender \
        mariadb-client \
		nodejs \
        supervisor \
		ttf-freefont \
		yarn \
	;

RUN set -eux; \
    install-php-extensions \
        apcu \
        gd \
        imap\
        intl \
        ldap \
        opcache \
        pdo_mysql \
    	sysvsem \
        zip \
    ;

###> recipes ###
###< recipes ###

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/conf.d/commsy.ini $PHP_INI_DIR/conf.d/
COPY docker/php/conf.d/commsy.prod.ini $PHP_INI_DIR/conf.d/

COPY docker/php/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
RUN mkdir -p /var/run/php

# wkhtmltopdf
COPY --from=surnet/alpine-wkhtmltopdf:3.17.0-0.12.6-full /bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer_upstream --link /composer /usr/bin/composer

WORKDIR /var/www/html

# build for production
ARG APP_ENV=prod

# prevent the reinstallation of vendors at every changes in the source code
COPY composer.json composer.lock symfony.lock ./
RUN set -eux; \
	composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress; \
	composer clear-cache

# prevent the reinstallation of node_modules at every changes in the source code
COPY webpack.config.js tsconfig.json package.json yarn.lock ./
COPY assets assets/
RUN set -eux; \
	yarn install; \
	yarn build; \
	rm -r assets; \
	rm tsconfig.json

# copy only specifically what we need
COPY .env ./
COPY VERSION ./
COPY bin bin/
COPY config config/
COPY legacy legacy/
COPY migrations migrations/
COPY public public/
COPY src src/
COPY templates templates/
COPY themes themes/
COPY translations translations/

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer dump-env prod; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync

VOLUME /var/www/html/var

COPY docker/php/docker-healthcheck.sh /usr/local/bin/docker-healthcheck
RUN chmod +x /usr/local/bin/docker-healthcheck

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]

COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

COPY docker/php/supervisord.conf /etc/supervisord.conf
COPY docker/php/supervisor.d /etc/supervisor/conf.d/

ENTRYPOINT ["docker-entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]

##############################################################################

# Dockerfile
FROM commsy_php AS commsy_php_dev

ENV APP_ENV=dev

ARG XDEBUG_VERSION=^3.2
RUN set -eux; \
	install-php-extensions xdebug-$XDEBUG_VERSION

RUN rm $PHP_INI_DIR/conf.d/commsy.prod.ini; \
	mv "$PHP_INI_DIR/php.ini" "$PHP_INI_DIR/php.ini-production"; \
	mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/conf.d/commsy.dev.ini $PHP_INI_DIR/conf.d/

CMD ["php-fpm"]

##############################################################################

FROM caddy_upstream AS commsy_caddy

WORKDIR /var/www/html

COPY --from=commsy_php /var/www/html/public public/
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
