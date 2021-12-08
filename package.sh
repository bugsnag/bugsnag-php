#!/bin/bash

if [[ "$(docker images -q bugsnag/php:5.5 2> /dev/null)" == "" ]]; then
    docker pull php:5.5-alpine
    docker build --no-cache -t bugsnag/php:5.5 .
fi

docker run --rm -v ${PWD}:/data -w /data --entrypoint ./packer.sh bugsnag/php:5.5
