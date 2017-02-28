#!/bin/bash

if ! gem list docker-sync -i > /dev/null; then
    echo "Installing docker-sync gem"
    gem install docker-sync
fi

case "$1" in
    clean)
        docker-compose -f docker-compose.yml -f docker-compose-dev.yml stop
        docker-compose -f docker-compose.yml -f docker-compose-dev.yml rm
        ;;
    rebuild)
        docker-compose -f docker-compose.yml -f docker-compose-dev.yml build
        ;;
    *)
        function finish {
            docker-sync-daemon clean
        }

        trap finish EXIT

        docker-sync-daemon start
        docker-compose -f docker-compose.yml -f docker-compose-dev.yml up
        ;;
esac