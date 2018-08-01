#!/bin/bash
set -e

if [ ! -e app/config/parameters.yml ]; then
        cp app/config/parameters.yml.dist app/config/parameters.yml
        chown www-data:www-data app/config/parameters.yml
fi

if [ ! -e legacy/etc/cs_config.php ]; then
    cp legacy/etc/cs_config.php-dist legacy/etc/cs_config.php
    chown www-data:www-data legacy/etc/cs_config.php
fi

sudo -H -u www-data bash -c 'php composer.phar install --no-interaction --optimize-autoloader'
sudo -H -u www-data bash -c 'php bin/console cache:clear --env=prod --no-debug'
sudo -H -u www-data bash -c 'php bin/console --no-interaction doctrine:migrations:migrate'
sudo -H -u www-data bash -c 'php bin/console fos:elastica:populate'
sudo -H -u www-data bash -c 'npm install'
sudo -H -u www-data bash -c 'bower --config.analytics=false install'
sudo -H -u www-data bash -c 'yarn run encore production'

exec "$@"