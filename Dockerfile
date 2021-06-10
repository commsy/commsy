# the different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target

# https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
ARG PHP_VERSION=7.4
ARG NGINX_VERSION=1.19

FROM php:${PHP_VERSION}-fpm-alpine AS commsy_php

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
		nodejs \
		ttf-freefont \
		yarn \
	;

# install gnu-libiconv and set LD_PRELOAD env to make iconv work fully on Alpine image.
# see https://github.com/docker-library/php/issues/240#issuecomment-763112749
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so

ARG APCU_VERSION=5.1.19
RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		icu-dev \
		libzip-dev \
		zlib-dev \
		libxml2-dev \
		openldap-dev \
		imap-dev \
		libpng-dev \
		jpeg-dev \
		freetype-dev \
	; \
	\
	docker-php-ext-configure zip; \
	docker-php-ext-configure imap --with-imap-ssl; \
	docker-php-ext-configure gd --with-freetype --with-jpeg; \
	docker-php-ext-install -j$(nproc) \
		intl \
		pdo_mysql \
		zip \
		soap \
		ldap \
		imap \
		gd \
	; \
	pecl install \
		apcu-${APCU_VERSION} \
		xdebug \
	; \
	pecl clear-cache; \
	docker-php-ext-enable \
		apcu \
		opcache \
		xdebug \
	; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
	\
	apk del .build-deps

# Composer
COPY --from=composer:1 /usr/bin/composer /usr/bin/composer

# wkhtmltopdf
COPY --from=surnet/alpine-wkhtmltopdf:3.13.5-0.12.6-full /bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf

# Set up php configuration
RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY docker/php/conf.d/commsy.prod.ini $PHP_INI_DIR/conf.d/commsy.ini
COPY docker/php/conf.d/commsy.pool.conf /usr/local/etc/php-fpm.d/

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /var/www/html

# build for production
ARG APP_ENV=prod

# prevent the reinstallation of vendors at every changes in the source code
COPY composer.json composer.lock symfony.lock ./
RUN set -eux; \
	composer install --prefer-dist --no-dev --no-scripts --no-progress; \
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
COPY bin bin/
COPY config config/
COPY legacy legacy/
COPY migrations migrations/
COPY public public/
COPY src src/
COPY templates templates/
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

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

##############################################################################

FROM nginx:${NGINX_VERSION}-alpine AS commsy_nginx

COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

COPY --from=commsy_php /var/www/html/public public/

##############################################################################

FROM nginx:${NGINX_VERSION}-alpine AS commsy_test_nginx

COPY docker/nginx/conf.d/test.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

#COPY --from=commsy_php /var/www/html/public public/