ARG PHP_VERSION=8.4
ARG PHP_EXTENSIONS

FROM ghcr.io/mlocati/php-extension-installer:2 AS ext_installer
FROM composer:2 AS composer

ARG PHP_VERSION
FROM php:${PHP_VERSION}-alpine

# Composer uses git to determine the root package version
RUN apk add --no-cache git bash

#
# Install necessary PHP extensions (Including those for external tools)
#
COPY --from=ext_installer /usr/bin/install-php-extensions /usr/local/bin/

ARG PHP_EXTENSIONS
RUN install-php-extensions ${PHP_EXTENSIONS}

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
