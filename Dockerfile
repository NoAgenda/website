FROM php:7.2-apache

# Set the working directory
WORKDIR /usr/src/application

# Update the DocumentRoot to the working directory
ENV APACHE_DOCUMENT_ROOT /usr/src/application/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable extensions
RUN a2enmod rewrite && \
    docker-php-ext-install pdo_mysql

# Install additional packages
RUN apt-get update && apt-get install -y \
    ffmpeg mplayer python-pip && \
    pip install numpy && \
    pip install scikits.talkbox && \
    pip install audio-offset-finder
