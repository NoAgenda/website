#!/usr/bin/env bash

# Download Composer
curl -sS https://getcomposer.org/installer | php

# Install third-party PHP dependencies
php composer.phar install

# Run scripts to setup database
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
php bin/console messenger:setup-transports

# Download and run build tools for assets
php bin/console app:refresh-cover-cache
yarn && yarn run build
