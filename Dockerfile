#syntax=docker/dockerfile:1

# Versions
FROM dunglas/frankenphp:1-php8.4 AS frankenphp_upstream
FROM node:22-trixie-slim AS node_upstream

# The different stages of this Dockerfile are meant to be built into separate
# images: https://docs.docker.com/build/building/multi-stage/

##############################################################################
# wkhtmltopdf
#
# Trixie carries no package any more, and Debian's own build always lacked the
# patched Qt that the header and footer options need. The project's final
# release deb (2023, archived) ships a statically linked binary that runs on
# trixie unchanged. Only the PDF CLI is taken: libwkhtmltox serves the language
# bindings, and the image renderer is switched off in knp_snappy.

FROM debian:bookworm-slim AS wkhtmltopdf_upstream

ARG TARGETARCH
ARG WKHTMLTOPDF_VERSION=0.12.6.1-3

RUN <<-'EOF'
	set -eux
	apt-get update
	apt-get install -y --no-install-recommends ca-certificates curl
	case "$TARGETARCH" in
		amd64) sha256=98ba0d157b50d36f23bd0dedf4c0aa28c7b0c50fcdcdc54aa5b6bbba81a3941d ;;
		arm64) sha256=b6606157b27c13e044d0abbe670301f88de4e1782afca4f9c06a5817f3e03a9c ;;
		*) echo "no wkhtmltopdf release for $TARGETARCH" >&2; exit 1 ;;
	esac
	curl -fsSLo /tmp/wkhtmltox.deb \
		"https://github.com/wkhtmltopdf/packaging/releases/download/${WKHTMLTOPDF_VERSION}/wkhtmltox_${WKHTMLTOPDF_VERSION}.bookworm_${TARGETARCH}.deb"
	echo "${sha256}  /tmp/wkhtmltox.deb" | sha256sum -c -
	dpkg -x /tmp/wkhtmltox.deb /wkhtmltox
	rm -rf /var/lib/apt/lists/*
EOF

##############################################################################
# Base image — what dev and the prod builder both need

FROM frankenphp_upstream AS commsy_php_base

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

WORKDIR /app

COPY --from=wkhtmltopdf_upstream --link /wkhtmltox/usr/local/bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf

# persistent deps
# The lib* entries carry wkhtmltopdf; mariadb-client is for looking into the
# database by hand and stays out of the production image.
# hadolint ignore=DL3008
RUN <<-EOF
	apt-get update
	apt-get install -y --no-install-recommends \
		file \
		fontconfig \
		fonts-freefont-ttf \
		git \
		libfontconfig1 \
		libfreetype6 \
		libjpeg62-turbo \
		libpng16-16 \
		libssl3 \
		libx11-6 \
		libxext6 \
		libxrender1 \
		mariadb-client
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
		zip
	rm -rf /var/lib/apt/lists/*
	# wkhtmltopdf looks up fonts through fontconfig. Building the cache here
	# keeps it out of the request that first renders a PDF, and out of a home
	# directory the runtime user may not be able to write.
	fc-cache -f
EOF

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

###> recipes ###
###< recipes ###

COPY --link docker/php/conf.d/commsy.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost:2019/metrics", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

##############################################################################
# Dev image — sources arrive through a bind mount, so nothing is copied in

FROM commsy_php_base AS commsy_php_dev

ENV APP_ENV=dev
ENV XDEBUG_MODE=off

RUN <<-EOF
	mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
	install-php-extensions xdebug
	git config --system --add safe.directory /app
EOF

# The front end is built inside the container. Trixie ships Node 20 and Encore
# wants 22, so it comes from the official image rather than from apt.
COPY --from=node_upstream --link /usr/local/bin/node /usr/local/bin/node
COPY --from=node_upstream --link /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN <<-EOF
	ln -sf ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
	ln -sf ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
EOF

COPY --link docker/php/conf.d/commsy.dev.ini $PHP_INI_DIR/app.conf.d/

##############################################################################
# Vendor — kept apart so the asset build can reach into it
#
# package.json pulls three Symfony UX packages straight out of vendor/, which
# is why this has to run before the assets.

FROM commsy_php_base AS commsy_vendor

ENV APP_ENV=prod

COPY --link composer.json composer.lock symfony.lock ./
RUN composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

##############################################################################
# Assets — Node stays out of the runtime image

FROM node_upstream AS commsy_assets

WORKDIR /app

COPY --from=commsy_vendor --link /app/vendor vendor/
COPY --link package.json package-lock.json .npmrc webpack.config.js tsconfig.json ./
RUN npm ci --no-audit --no-fund

COPY --link assets assets/
RUN npm run build

##############################################################################
# Builder for the prod image

FROM commsy_php_base AS commsy_php_builder

ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link docker/php/conf.d/commsy.prod.ini $PHP_INI_DIR/app.conf.d/

COPY --from=commsy_vendor --link /app/vendor vendor/
COPY --link --exclude=docker . ./
COPY --from=commsy_assets --link /app/public/build public/build/

RUN <<-EOF
	mkdir -p var/cache var/log
	composer dump-autoload --classmap-authoritative --no-dev
	composer dump-env prod
	composer run-script --no-dev post-install-cmd
	chmod +x bin/console
	chmod -R g=u var
	sync
EOF

# Collect the shared libraries the runtime needs, so the final image can start
# from a bare Debian rather than carry the whole build toolchain. wkhtmltopdf
# is in the list because it is the one external program the application runs.
# hadolint ignore=DL3008,SC3054,DL4006
RUN <<-'EOF'
	apt-get update
	apt-get install -y --no-install-recommends libtree
	mkdir -p /tmp/libs
	BINARIES=(frankenphp php file wkhtmltopdf)
	for target in $(printf '%s\n' "${BINARIES[@]}" | xargs -I{} which {}) \
		$(find "$(php -r 'echo ini_get("extension_dir");')" -maxdepth 2 -name "*.so"); do
		libtree -pv "$target" 2>/dev/null | grep -oP '(?:── )\K/\S+(?= \[)' | while IFS= read -r lib; do
			[ -f "$lib" ] && cp -n "$lib" /tmp/libs/
		done
	done
	rm -rf /var/lib/apt/lists/*
EOF

##############################################################################
# Prod image

FROM debian:13-slim AS commsy_php_prod

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

ENV APP_ENV=prod
ENV PHP_INI_SCAN_DIR=":/usr/local/etc/php/app.conf.d"

COPY --from=commsy_php_builder /usr/local/bin/frankenphp /usr/local/bin/frankenphp
COPY --from=commsy_php_builder /usr/local/bin/php /usr/local/bin/php
COPY --from=commsy_php_builder /usr/local/bin/docker-php-entrypoint /usr/local/bin/docker-php-entrypoint
COPY --from=commsy_php_builder /usr/local/bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf
COPY --from=commsy_php_builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=commsy_php_builder /tmp/libs /usr/lib

COPY --from=commsy_php_builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
COPY --from=commsy_php_builder /usr/local/etc/php/php.ini /usr/local/etc/php/php.ini
COPY --from=commsy_php_builder /usr/local/etc/php/app.conf.d /usr/local/etc/php/app.conf.d

COPY --from=commsy_php_builder /etc/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

# CA certificates for TLS, file/libmagic for Symfony MIME type detection
COPY --from=commsy_php_builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt
COPY --from=commsy_php_builder /etc/ssl/openssl.cnf /etc/ssl/openssl.cnf
COPY --from=commsy_php_builder /usr/bin/file /usr/bin/file
COPY --from=commsy_php_builder /usr/lib/file/magic.mgc /usr/lib/file/magic.mgc

# wkhtmltopdf draws text through fontconfig, which needs its configuration,
# the fonts themselves and the cache built alongside them.
COPY --from=commsy_php_builder /etc/fonts /etc/fonts
COPY --from=commsy_php_builder /usr/share/fonts /usr/share/fonts
COPY --from=commsy_php_builder /var/cache/fontconfig /var/cache/fontconfig

ENV OPENSSL_CONF=/etc/ssl/openssl.cnf XDG_CONFIG_HOME=/config XDG_DATA_HOME=/data SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

RUN <<-EOF
	mkdir -p /data/caddy /config/caddy
	chown -R www-data:0 /data /config
	chmod -R g=u /data /config
	# Remove setuid/setgid bits
	find / -perm /6000 -type f -exec chmod a-s {} + 2>/dev/null || true
EOF

COPY --link --exclude=var --from=commsy_php_builder /app /app
# Group 0 + g=u for arbitrary-UID runtimes (e.g. OpenShift), and so a named
# volume mounted over one of these inherits a writable owner.
COPY --chown=www-data:0 --from=commsy_php_builder /app/var /app/var
RUN <<-EOF
	mkdir -p /app/files
	chown www-data:0 /app/files
	# The JWT keypair is generated on first start, not baked into the image —
	# it is a secret, and this image is public. The rootless user therefore
	# needs to be able to write it.
	mkdir -p /app/config/jwt
	chown www-data:0 /app/config/jwt
	chmod -R g=u /app/var /app/files /app/config/jwt
EOF

COPY --link --chmod=755 docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

USER www-data

WORKDIR /app

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD php -r 'exit(false === @file_get_contents("http://localhost:2019/metrics", context: stream_context_create(["http" => ["timeout" => 5]])) ? 1 : 0);'

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
