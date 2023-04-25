#!/bin/bash

set -e

if [ "$1" = 'bin/console' ]; then
  echo "Waiting for app to be ready..."

  ATTEMPTS_LEFT_TO_REACH_APPLICATION=60
  until [ $ATTEMPTS_LEFT_TO_REACH_APPLICATION -eq 0 ] || nc -z ${FPM_HOST} ${FPM_PORT}; do
    sleep 5

    ATTEMPTS_LEFT_TO_REACH_APPLICATION=$((ATTEMPTS_LEFT_TO_REACH_APPLICATION - 1))
    echo "Still waiting for app to be ready... Is it reachable? $ATTEMPTS_LEFT_TO_REACH_APPLICATION attempts left"
  done

  if [ $ATTEMPTS_LEFT_TO_REACH_APPLICATION -eq 0 ]; then
    echo "The app is not up or not reachable"

    exit 1
  else
    echo "The app is now ready and reachable"
  fi
fi

exec docker-php-entrypoint "$@"
