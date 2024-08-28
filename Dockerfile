#syntax=docker/dockerfile:1.4

# Versions
FROM dunglas/frankenphp:1-php8.3 AS frankenphp_upstream

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target


# Base FrankenPHP image
FROM frankenphp_upstream AS frankenphp_base

WORKDIR /app

VOLUME /app/var/

# persistent / runtime deps
# hadolint ignore=DL3008
RUN apt-get update && apt-get install -y --no-install-recommends \
	acl \
    autoconf \
	file \
	gettext \
	git \
	mariadb-client \
	supervisor \
    wkhtmltopdf \
	&& rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs && \
    corepack enable && \
    corepack prepare yarn@stable --activate

RUN set -eux; \
	install-php-extensions \
		@composer \
		apcu \
    	gd \
    	imap \
		intl \
    	ldap \
		opcache \
    	pdo_mysql \
		sysvsem \
		zip \
	;

# wkhtmltopdf
#COPY --from=surnet/alpine-wkhtmltopdf:3.17.0-0.12.6-full /bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

###> recipes ###
###< recipes ###

COPY --link docker/frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 docker/frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

COPY --link docker/frankenphp/supervisord.conf /etc/supervisord.conf
COPY --link docker/frankenphp/supervisor.d /etc/supervisor/conf.d/

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1
CMD ["supervisord", "-c", "/etc/supervisord.conf"]

# Dev FrankenPHP image
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev XDEBUG_MODE=off

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; \
	install-php-extensions \
		xdebug \
	;

COPY --link docker/frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch" ]

# Prod FrankenPHP image
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link docker/frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/
COPY --link docker/frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile

# prevent the reinstallation of vendors at every changes in the source code
COPY --link composer.* symfony.* ./
RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# prevent the reinstallation of node_modules at every changes in the source code
COPY --link webpack.config.js tsconfig.json package.json yarn.lock ./
COPY --link assets assets/
RUN set -eux; \
	yarn install; \
	yarn build; \
	rm -r assets; \
	rm tsconfig.json

# copy sources
COPY --link . ./
RUN rm -Rf docker/

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer dump-env prod; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync;
