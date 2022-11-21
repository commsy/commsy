# the different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target

# https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
ARG PHP_VERSION=8.0
ARG CADDY_VERSION=2

FROM php:${PHP_VERSION}-fpm-alpine AS commsy_php

# php extensions installer: https://github.com/mlocati/docker-php-extension-installer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

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

# install gnu-libiconv and set LD_PRELOAD env to make iconv work fully on Alpine image.
# see https://github.com/docker-library/php/issues/240#issuecomment-763112749
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so

RUN set -eux; \
    install-php-extensions \
        apcu \
        gd \
        imap\
        intl \
        ldap \
        opcache \
        pdo_mysql \
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
COPY --from=surnet/alpine-wkhtmltopdf:3.13.5-0.12.6-full /bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer/composer:2-bin /composer /usr/bin/composer

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
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

##############################################################################

# Dockerfile
FROM commsy_php AS commsy_php_dev

ENV APP_ENV=dev PHP_IDE_CONFIG="serverName=commsy"

ARG XDEBUG_VERSION=^3.1
RUN set -eux; \
	install-php-extensions xdebug-$XDEBUG_VERSION

COPY docker/php/conf.d/commsy.dev.ini $PHP_INI_DIR/conf.d/

CMD ["php-fpm"]

##############################################################################

FROM caddy:${CADDY_VERSION} AS commsy_caddy

WORKDIR /srv/app

COPY --from=commsy_php /var/www/html/public public/
COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile