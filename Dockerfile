FROM php:8.1-fpm AS base

WORKDIR /srv/www

ARG GITHUB_TOKEN

ENV FPM_HOST=app
ENV FPM_PORT=9000

VOLUME /srv/www/var

# Install persistent & runtime dependencies
RUN set -eux; \
    apt-get update; \
    apt-get install --no-install-recommends -y acl git netcat procps; \
    rm -rf /var/lib/apt/lists/*

# Install media utilities
RUN set -eux; \
    apt-get update; \
    apt-get install --no-install-recommends -y ffmpeg mplayer; \
    apt-get install -y python3-pip; \
    pip install --user git+https://github.com/flutterfromscratch/audio-offset-finder.git; \
    rm -rf /var/lib/apt/lists/*

ENV PATH="/root/.local/bin:${PATH}"

# Install PHP extensions
RUN set -eux; \
    apt-get update; \
    apt-get install --no-install-recommends -y libmagickwand-dev; \
    pecl install imagick; \
	docker-php-ext-enable imagick; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    apt-get update; \
    apt-get install --no-install-recommends -y libicu-dev; \
    docker-php-ext-install intl; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    docker-php-ext-install pdo_mysql

RUN set -eux; \
    apt-get update; \
    apt-get install --no-install-recommends -y libzip-dev unzip; \
	docker-php-ext-install zip; \
    rm -rf /var/lib/apt/lists/*

# Install Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN if [ ! -z "$GITHUB_TOKEN" ]; then composer config --global github-oauth.github.com $GITHUB_TOKEN; fi

FROM node:16.14-alpine AS assets

WORKDIR /srv/www

ENV FPM_HOST=app
ENV FPM_PORT=9000

# Install dependencies
COPY package.json package-lock.json ./
RUN set -eux; \
    npm install; \
	npm cache clean --force

# Compile assets
COPY .babelrc .eslintrc.json jest.config.js webpack.config.js ./
COPY assets assets/
RUN set -eux; \
    npm run production

# Set up entrypoint
COPY docker/assets-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["npm", "run", "watch"]

FROM base AS app

# Copy application directory contents
COPY license.markdown readme.markdown ./
COPY composer.json composer.lock symfony.lock ./
COPY .env ./
COPY .env.test phpunit.xml.dist ./
COPY bin bin/
COPY config config/
COPY migrations migrations/
COPY public public/
COPY src src/
COPY templates templates/
COPY tests tests/
COPY translations translations/

RUN mkdir -p \
        docker/storage/chat_archives \
        docker/storage/chat_logs \
        docker/storage/covers \
        docker/storage/episode_parts \
        docker/storage/episodes \
        docker/storage/livestream_recordings \
        docker/storage/shownotes \
        docker/storage/transcripts

COPY --from=assets /srv/www/public public/

# Run Composer commands
RUN set -eux; \
    composer install --no-autoloader --no-dev --no-progress --no-scripts --prefer-dist; \
    composer clear-cache; \
    composer dump-autoload --classmap-authoritative; \
    mkdir -p var/cache var/log public/media; \
    chmod +x bin/console; \
    APP_ENV=prod composer run-script post-install-cmd

# Set up entrypoints
COPY docker/app-entrypoint.bash /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

COPY docker/php-entrypoint.bash /usr/local/bin/app-php-entrypoint
RUN chmod +x /usr/local/bin/app-php-entrypoint

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

FROM nginx:alpine AS http

WORKDIR /srv/www

ENV FPM_HOST=app
ENV FPM_PORT=9000

# Copy Nginx configuration
COPY docker/nginx/app.conf.template /etc/nginx/templates/
RUN rm /etc/nginx/conf.d/default.conf

# Copy application directory contents
COPY --from=app /srv/www/public public/
