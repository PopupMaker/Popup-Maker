#!/usr/bin/env bash

function version_gt() { test "$(printf '%s\n' "$@" | sort -V | head -n 1)" != "$1"; }

if [ TRAVIS_PHP_VERSION = 7 ] || version_gt TRAVIS_PHP_VERSION 7; then
     COMPOSER=composer-travis-php7.json composer install
else
    COMPOSER=composer-travis-php5.json composer install
fi