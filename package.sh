#!/bin/bash

if [[ "$(docker images -q bugsnag/php:5.5 2> /dev/null)" == "" ]]; then
    docker build .docker -t bugsnag/php:5.5
fi

docker run --rm -v ${PWD}:/data -w /data --entrypoint ./packer.sh bugsnag/php:5.5
