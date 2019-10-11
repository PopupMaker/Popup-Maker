#!/usr/bin/env bash

echo "TRAVIS PHP VERSION $1";

# https://stackoverflow.com/a/24067243
function version_gt() { test "$(printf '%s\n' "$@" | sort -V | head -n 1)" != "$1"; }

if [ $1 = 7 ] || version_gt $1 7; then
    echo "composer install composer-travis-php7.json";
    #COMPOSER=composer-travis-php7.json composer install
else
    echo "composer install composer-travis-php5.json";
    #COMPOSER=composer-travis-php5.json composer install
fi