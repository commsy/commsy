#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if [ "$1" = 'supervisord' ] || [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    mkdir -p var/cache var/cache/htmlpurifier var/log
    setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var files
    setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var files

    if [ "$APP_ENV" != 'prod' ]; then
        composer install --prefer-dist --no-progress --no-suggest --no-interaction
    fi

    bin/console lexik:jwt:generate-keypair --skip-if-exists

    echo "Waiting for db to be ready..."
    ATTEMPTS_LEFT_TO_REACH_DATABASE=60
    until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; do
        sleep 1
        ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE-1))
        echo "Still waiting for db to be ready... Or maybe the db is not reachable. $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left"
    done

    if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
        echo "The db is not up or not reachable"
        exit 1
    else
        echo "The db is now ready and reachable"
    fi

    if bin/console doctrine:migrations:current --no-ansi | grep -q 'No migration executed yet'; then
        echo "Loading initial database dump"
        bin/console dbal:run-sql --no-interaction "$(cat src/Resources/fixtures/initial.sql)"
    fi

    if [ "$( find ./migrations -iname '*.php' -print -quit )" ]; then
        echo "Running database migrations"
        bin/console doctrine:migrations:migrate --no-interaction
    fi
fi

exec "$@"
