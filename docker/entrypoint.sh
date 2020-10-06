#!/bin/bash
set -e

if [ "$1" = 'php-fpm' ] || [ "$1" = 'bin/console' ]; then
	mkdir -p var/cache var/log
	mkdir -p public/media

	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var public/media
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var public/media

	if [ "$APP_ENV" != 'prod' ]; then
    composer install --prefer-dist --no-autoloader --no-scripts --no-progress --no-suggest
    composer clear-cache
    composer dump-autoload --classmap-authoritative
	fi
fi

exec docker-php-entrypoint "$@"
