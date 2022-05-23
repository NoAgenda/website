#!/bin/bash

set -e

if [ "$1" = 'php-fpm' ] || [ "$1" = 'bin/console' ]; then
  mkdir -p public/media var/cache var/log

  if [ "$APP_ENV" != 'prod' ]; then
    composer install --prefer-dist --no-autoloader --no-progress --no-scripts --no-interaction
    composer clear-cache --no-interaction
    composer dump-autoload --no-interaction
    composer run-script post-install-cmd --no-interaction
  fi

  >&2 echo "Waiting for database to be ready..."
  until bin/console dbal:run-sql "select 1" >/dev/null 2>&1; do
    sleep 1
  done

  bin/console doctrine:migrations:migrate --no-interaction
  bin/console messenger:setup-transports
fi

echo "Ready to serve requests"

exec docker-php-entrypoint "$@"
