FROM php:7.2-fpm AS noagenda_app

WORKDIR /srv/www

# Install additional packages
RUN apt-get update; apt-get install --no-install-recommends -y \
    acl libmagickwand-dev netcat unzip \
    ffmpeg mplayer

RUN apt-get update; apt-get install -y python-pip; \
    pip install numpy; \
    pip install scikits.talkbox; \
    pip install audio-offset-finder

# Enable extensions
RUN pecl install imagick; \
	docker-php-ext-enable imagick; \
    docker-php-ext-install pdo_mysql zip

# Install Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application directory contents
COPY .env .env.local ./
COPY composer.json composer.lock symfony.lock ./
COPY bin bin/
COPY config config/
COPY public public/
COPY src src/
COPY templates templates/
COPY translations translations/

# Run Composer commands
RUN composer install --prefer-dist --no-autoloader --no-scripts --no-progress --no-suggest; \
    composer clear-cache; \
    composer dump-autoload --classmap-authoritative; \
    mkdir -p var/cache var/log; \
    composer run-script post-install-cmd

# Expose port 9000
EXPOSE 9000

# Set up entrypoints
COPY docker/app-entrypoint.bash /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

COPY docker/php-entrypoint.bash /usr/local/bin/app-php-entrypoint
RUN chmod +x /usr/local/bin/app-php-entrypoint

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

FROM node:12.0-alpine AS noagenda_assets

WORKDIR /srv/www

# Install dependencies
COPY package.json yarn.lock ./
RUN yarn install; \
	yarn cache clean

# Compile assets
COPY webpack.config.js .babelrc ./
COPY assets assets/
RUN yarn run production

# Set up entrypoint
COPY docker/assets-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["yarn", "run", "watch"]

FROM mysql:8.0 AS noagenda_database

ARG MYSQL_DATABASE
ARG MYSQL_USER
ARG MYSQL_PASSWORD
ARG MYSQL_ROOT_PASSWORD

ENV MYSQL_DATABASE=${MYSQL_DATABASE}
ENV MYSQL_USER=${MYSQL_USER}
ENV MYSQL_PASSWORD=${MYSQL_PASSWORD}
ENV MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}

# Copy MySQL configuration
COPY docker/mysql/my.cnf /etc/my.cnf
RUN chmod 0755 /etc/my.cnf

FROM nginx:alpine AS noagenda_http

WORKDIR /srv/www

# Copy Nginx configuration
COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf

# Copy application directory contents
COPY --from=noagenda_app /srv/www/public public/
COPY --from=noagenda_assets /srv/www/public public/
