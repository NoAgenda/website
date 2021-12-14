FROM node:16.8-alpine AS noagenda_assets

WORKDIR /srv/www

ENV FPM_HOST=app
ENV FPM_PORT=9000

# Install dependencies
COPY package.json package-lock.json ./
RUN npm install; \
	npm cache clean --force

# Compile assets
COPY webpack.config.js .babelrc ./
COPY assets assets/
RUN npm run production

# Set up entrypoint
COPY docker/assets-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["npm", "run", "watch"]

FROM php:8.0-fpm AS noagenda_app

WORKDIR /srv/www

ENV FPM_HOST=app
ENV FPM_PORT=9000

# Install persistent & runtime dependencies
RUN apt-get update; \
    apt-get install --no-install-recommends -y acl git netcat procps

# Install media utilities
RUN apt-get update; \
    apt-get install --no-install-recommends -y ffmpeg mplayer; \
    apt-get install -y python3-pip; \
    pip install --user git+https://github.com/abramhindle/audio-offset-finder.git

# Install PHP extensions
RUN apt-get update; \
    apt-get install --no-install-recommends -y libmagickwand-dev; \
    pecl install imagick; \
	docker-php-ext-enable imagick

RUN apt-get update; \
    apt-get install --no-install-recommends -y libicu-dev; \
    docker-php-ext-install intl

RUN docker-php-ext-install pdo_mysql

RUN apt-get update; \
    apt-get install --no-install-recommends -y libzip-dev unzip; \
	docker-php-ext-install zip

# Install Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application directory contents
COPY .env ./
COPY composer.json composer.lock symfony.lock ./
COPY bin bin/
COPY config config/
COPY migrations migrations/
COPY public public/
COPY src src/
COPY templates templates/
COPY translations translations/

COPY --from=noagenda_assets /srv/www/public public/

# Run Composer commands
RUN composer install --prefer-dist --no-autoloader --no-progress --no-scripts; \
    composer clear-cache; \
    composer dump-autoload --classmap-authoritative; \
    mkdir -p var/cache var/log public/media; \
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

FROM nginx:alpine AS noagenda_http

WORKDIR /srv/www

ENV FPM_HOST=app
ENV FPM_PORT=9000

# Copy Nginx configuration
COPY docker/nginx/app.conf.template /etc/nginx/templates/
RUN rm /etc/nginx/conf.d/default.conf

# Copy application directory contents
COPY --from=noagenda_app /srv/www/public public/
