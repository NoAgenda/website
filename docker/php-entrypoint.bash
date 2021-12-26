#!/bin/bash

set -e

if [ "$1" = 'bin/console' ]; then
  >&2 echo "Waiting for app to be ready..."
  until nc -z ${FPM_HOST} ${FPM_PORT}; do
    sleep 1
  done
fi

exec docker-php-entrypoint "$@"
