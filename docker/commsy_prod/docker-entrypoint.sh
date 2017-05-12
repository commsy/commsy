#!/bin/bash
set -e

if [ "$1" == php-fpm ]; then
    : ${COMMSY_DB_HOST:=db}
    : ${COMMSY_DB_USER:=${MYSQL_ENV_MYSQL_USER:-root}}
    : ${COMMSY_DB_NAME:=${MYSQL_ENV_MYSQL_DATABASE:-commsy}}

    if [ "$COMMSY_DB_USER" = 'root' ]; then
        : ${COMMSY_DB_PASSWORD:=$MYSQL_ENV_MYSQL_ROOT_PASSWORD}
    fi
    : ${COMMSY_DB_PASSWORD:=$MYSQL_ENV_MYSQL_PASSWORD}

    if [ -z "$COMMSY_DB_PASSWORD" ]; then
        echo >&2 'error: missing required COMMSY_DB_PASSWORD environment variable'
        echo >&2 '  Did you forget to -e COMMSY_DB_PASSWORD=... ?'
        echo >&2
        echo >&2 '  (Also of interest might be COMMSY_DB_USER and COMMSY_DB_NAME.)'
        exit 1
    fi

    if ! [ -e VERSION ]; then
        echo >&2 "CommSy not found in $(pwd) - copying now..."
        if [ "$(ls -A)" ]; then
            echo >&2 "WARNING: $(pwd) is not empty - press Ctrl+C now if this is an error!"
            ( set -x; ls -A; sleep 10 )
        fi

        tar cf - --one-file-system -C /usr/src/commsy . | tar xf -
        echo >&2 "Complete! CommSy has been successfully copied to $(pwd)"
    fi

    # : ${SYMFONY__DATABASE__HOST:=$COMMSY_DB_HOST}
    # : ${SYMFONY__DATABASE__NAME:=$COMMSY_DB_NAME}
    # : ${SYMFONY__DATABASE__USER:=$COMMSY_DB_USER}
    # : ${SYMFONY__DATABASE__PASSWORD:=$COMMSY_DB_PASSWORD}

    # export SYMFONY__DATABASE__HOST=$COMMSY_DB_HOST
    # export SYMFONY__DATABASE__NAME=$COMMSY_DB_NAME
    # export SYMFONY__DATABASE__USER=$COMMSY_DB_USER
    # export SYMFONY__DATABASE__PASSWORD=$COMMSY_DB_PASSWORD

    # export SYMFONY__LOCALE=de

    # export SYMFONY__SECRET=$(head -c1M /dev/urandom | sha1sum | cut -d' ' -f1)

    # export SYMFONY__ELASTIC__HOST=elastic


    if [ ! -e app/config/parameters.yml ]; then
        cp app/config/parameters.yml.dist app/config/parameters.yml
        chown www-data:www-data app/config/parameters.yml
    fi

    if [ ! -e legacy/etc/cs_config.php ]; then
        cp legacy/etc/cs_config.php-dist legacy/etc/cs_config.php
        chown www-data:www-data legacy/etc/cs_config.php
    fi

    # see http://stackoverflow.com/a/2705678/433558
    # sed_escape_lhs() {
    #     echo "$@" | sed 's/[]\/$*.^|[]/\\&/g'
    # }
    # sed_escape_rhs() {
    #     echo "$@" | sed 's/[\/&]/\\&/g'
    # }
    # php_escape() {
    #     php -r 'var_export(('$2') $argv[1]);' "$1"
    # }
    # set_config() {
    #     key="$1"
    #     value="$2"
    #     var_type="${3:-string}"
    #     start="(['\"])$(sed_escape_lhs "$key")\2\s*,"
    #     end="\);"
    #     if [ "${key:0:1}" = '$' ]; then
    #         start="^(\s*)$(sed_escape_lhs "$key")\s*="
    #         end=";"
    #     fi
    #     sed -ri "s/($start\s*).*($end)$/\1$(sed_escape_rhs "$(php_escape "$value" "$var_type")")\3/" app/config/parameters.yml
    # }

    #set_config ''
    
    sudo -H -u www-data bash -c 'php composer.phar install --no-interaction --optimize-autoloader'
    sudo -H -u www-data bash -c 'php bin/console cache:clear --env=prod --no-debug'
    sudo -H -u www-data bash -c 'php bin/console --no-interaction doctrine:migrations:migrate'
    sudo -H -u www-data bash -c 'php bin/console fos:elastica:populate'
    sudo -H -u www-data bash -c 'npm install'
    sudo -H -u www-data bash -c 'bower --config.analytics=false install'
    sudo -H -u www-data bash -c 'gulp --prod'
fi

exec "$@"