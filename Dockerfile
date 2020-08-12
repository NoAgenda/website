FROM php:7.2-fpm

# Set working directory
WORKDIR /srv/www

# Install additional packages
RUN apt-get update; apt-get install -y \
    supervisor \
    ffmpeg mplayer python-pip; \
    pip install numpy; \
    pip install scikits.talkbox; \
    pip install audio-offset-finder

# Enable extensions
RUN apt-get update; apt-get install -y \
    libmagickwand-dev --no-install-recommends; \
    a2enmod rewrite; \
    pecl install imagick; \
	docker-php-ext-enable imagick; \
    docker-php-ext-install pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Add user for application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy application directory contents
RUN chown www:www /srv/www
COPY --chown=www:www . /srv/www

# Change current user to www
USER www

# Run Composer commands
RUN composer install --prefer-dist --no-autoloader --no-scripts --no-progress --no-suggest; \
    composer clear-cache; \
    composer dump-autoload --classmap-authoritative; \
    mkdir -p var/cache var/log; \
    composer run-script post-install-cmd

# Expose port 9000
EXPOSE 9000

# Set up entrypoint
RUN chmod +x docker/entrypoint.sh
CMD ["/srv/www/docker/entrypoint.sh"]
