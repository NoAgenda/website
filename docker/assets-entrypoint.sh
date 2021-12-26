#!/bin/sh

set -e

if [ "$1" = 'node' ] || [ "$1" = 'npm' ]; then
  npm install

  >&2 echo "Waiting for app to be ready..."
  until nc -z ${FPM_HOST} ${FPM_PORT}; do
    sleep 1
  done
fi

exec "$@"
