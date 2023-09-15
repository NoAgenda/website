#!/bin/sh

set -e

if [ "$1" = 'npm' ]; then
  npm install
fi

exec docker-entrypoint.sh "$@"
