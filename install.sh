#!/usr/bin/env bash

sudo -H -u www-data bash -c 'php composer.phar install'
sudo -H -u www-data bash -c 'php bin/console doctrine:fixtures:load --append'
sudo -H -u www-data bash -c 'php bin/console --no-interaction doctrine:migrations:migrate'
sudo -H -u www-data bash -c 'php bin/console fos:elastica:populate'
npm install
bower install --allow-root
sudo -H -u www-data bash -c 'gulp'
rm -r var/cache/dev/