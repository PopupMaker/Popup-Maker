if [ TRAVIS_PHP_VERSION >= 7 ]
then
    COMPOSER=composer-travis-php7.json composer install
else
    COMPOSER=composer-travis-php5.json composer install
fi