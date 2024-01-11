#syntax=docker/dockerfile:1.4

# Versions
FROM dunglas/frankenphp:latest-alpine AS frankenphp_upstream
FROM composer/composer:2-bin AS composer_upstream

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target

# Base FrankenPHP image
FROM frankenphp_upstream AS frankenphp_base

WORKDIR /app
VOLUME /app/files

# persistent / runtime deps
# hadolint ignore=DL3018
RUN apk add --no-cache \
		acl \
		autoconf \
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

COPY --link docker/frankenphp/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link --chmod=755 docker/frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer_upstream --link /composer /usr/bin/composer

# wkhtmltopdf
COPY --from=surnet/alpine-wkhtmltopdf:3.18.0-0.12.6-full /bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1

COPY docker/frankenphp/supervisord.conf /etc/supervisord.conf
COPY docker/frankenphp/supervisor.d /etc/supervisor/conf.d/

#CMD ["supervisord", "-c", "/etc/supervisord.conf"]
CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]

# Dev FrankenPHP image
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev XDEBUG_MODE=off
VOLUME /app/var/

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; \
	install-php-extensions \
		xdebug \
	;

COPY --link docker/frankenphp/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

# Prod FrankenPHP image
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link docker/frankenphp/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/
COPY --link docker/frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile

# prevent the reinstallation of vendors at every changes in the source code
COPY --link composer.* symfony.* ./
RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# prevent the reinstallation of node_modules at every changes in the source code
COPY webpack.config.js tsconfig.json package.json yarn.lock ./
COPY assets assets/
RUN set -eux; \
	yarn install; \
	yarn build; \
	rm -r assets; \
	rm tsconfig.json \

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
	chmod +x bin/console; sync;
