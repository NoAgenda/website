#!/bin/sh

set -e

if [ "$1" = 'node' ] || [ "$1" = 'yarn' ]; then
	yarn install

	>&2 echo "Waiting for app to be ready..."
	until nc -z "app" "9000"; do
		sleep 1
	done
fi

exec "$@"
