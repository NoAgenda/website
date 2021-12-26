#!/bin/bash

set -e

if [ "$1" = 'php-fpm' ] || [ "$1" = 'bin/console' ]; then
	mkdir -p var/cache var/log public/media

	setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var public/media
	setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var public/media

	if [ ! -z "$APP_STORAGE_PATH" ]; then
	  mkdir -p $APP_STORAGE_PATH/chat_logs $APP_STORAGE_PATH/chat_messages $APP_STORAGE_PATH/crawler_logs
	  mkdir -p $APP_STORAGE_PATH/episode_covers $APP_STORAGE_PATH/episode_parts $APP_STORAGE_PATH/episode_recordings
	  mkdir -p $APP_STORAGE_PATH/livestream_recordings $APP_STORAGE_PATH/shownotes $APP_STORAGE_PATH/transcripts

	  setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX $APP_STORAGE_PATH
	  setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX $APP_STORAGE_PATH
	fi

	if [ "$APP_ENV" != 'prod' ]; then
    composer install --prefer-dist --no-autoloader --no-progress --no-scripts --no-interaction
    composer clear-cache
    composer dump-autoload --classmap-authoritative
    composer run-script post-install-cmd
	fi

  >&2 echo "Waiting for database to be ready..."
	until bin/console doctrine:query:sql "select 1" >/dev/null 2>&1; do
		sleep 1
	done

  bin/console doctrine:migrations:migrate --no-interaction
  bin/console messenger:setup-transports
fi

echo "Ready to serve requests"

exec docker-php-entrypoint "$@"
