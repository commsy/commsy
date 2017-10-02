#!/usr/bin/env bash

case "$1" in
    wipe)
        echo "Stopping CommSy container";
        docker-compose -f docker-compose.yml -f docker-compose-dev.yml stop

        echo "Removing all CommSy container"
        docker-compose -f docker-compose.yml -f docker-compose-dev.yml rm

        echo "Removing all docker images";
        docker rmi $(docker images -a -q)

        echo "Removing dangling volumes"
        docker volume rm $(docker volume ls -f dangling=true -q)

        echo "Wiping all container data"
        rm -r ./.data/

        echo "Removing previously installed brew packages"
        brew uninstall unsion fswatch

        echo "Removing docker-sync gems"
        gem uninstall docker-sync -a -x

        echo "Please close your terminal session and execute ./dev.sh start"
        ;;

    build)
        echo "Building CommSy container";
        docker-compose -f docker-compose.yml -f docker-compose-dev.yml build
        ;;

    start)
        if [ ! -e "../legacy/etc/cs_config.php" ]; then
            echo "Legacy cs_config.php seems not to be present, copying..."
            cp -p ../legacy/etc/cs_config.php-dist ../legacy/etc/cs_config.php
        fi

        if [ -d "./data/logs" ]; then
            echo "Clearing old logs..."
            rm -r ./data/logs/
        fi

        docker-compose -f docker-compose.yml -f docker-compose-dev.yml up

        ;;

    *)
        ;;
esac