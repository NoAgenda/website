#!/bin/bash

set -e

if [ "$1" = 'bin/console' ]; then
	>&2 echo "Waiting for app to be ready..."
	until nc -z "app" "9000"; do
		sleep 1
	done
fi

exec docker-php-entrypoint "$@"
