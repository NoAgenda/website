FROM php:7.2-apache

# Set the working directory
WORKDIR /usr/src/application

# Update the DocumentRoot to the working directory
ENV APACHE_DOCUMENT_ROOT /usr/src/application/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Install additional packages
RUN apt-get update && apt-get install -y \
    supervisor \
    ffmpeg mplayer python-pip && \
    pip install numpy && \
    pip install scikits.talkbox && \
    pip install audio-offset-finder

# Enable extensions
RUN apt-get update && apt-get install -y \
    libmagickwand-dev --no-install-recommends && \
    a2enmod rewrite && \
    pecl install imagick && \
	docker-php-ext-enable imagick && \
    docker-php-ext-install pdo_mysql && \
    docker-php-ext-install zip

# Install Node.js
RUN apt-get update && apt-get install -y gnupg
RUN curl -sL https://deb.nodesource.com/setup_12.x | bash - && \
  	apt-get install -y nodejs

# Install Yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - && \
    echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list && \
    apt-get update && apt-get install yarn
