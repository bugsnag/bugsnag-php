FROM php:5.5-alpine

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN apk update && apk upgrade

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

ENTRYPOINT ["php"]
