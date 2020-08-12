#!/bin/bash

if [ "$APP_ENV" != 'prod' ]; then
    # Install Composer dependencies again for development environments in case
    # the folder has ben mounted as a volume and the dependencies have disappeared
    composer install --prefer-dist --no-autoloader --no-scripts --no-progress --no-suggest
    composer clear-cache
    composer dump-autoload --classmap-authoritative
fi

# Run Composer scripts
composer run-script post-install-cmd

# Start FPM server
php-fpm
