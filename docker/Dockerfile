FROM php:7.4-apache

ENV BUILD_PACKAGES \
    libbz2-dev \
    libc-client-dev \
    libcurl4-openssl-dev \
    libfreetype6-dev \
    libgd-dev \
    libicu-dev \
    libkrb5-dev \
    libmagickcore-dev \
    libmagickwand-dev \
    libonig-dev \
    libmcrypt-dev \
    libmemcached-dev \
    libtidy-dev \
    libxml2-dev \
    libxslt-dev \
    libz-dev \
    libzip-dev

RUN apt-get update && apt-get install -y --no-install-recommends \
    bzip2 \
    cron \
    g++ \
    gettext \
    git \
    gnupg \
    imagemagick \
    libfreetype6 \
    libgd3 \
    libmcrypt4 \
    libmemcached11 \
    libmemcachedutil2 \
    libsodium23 \
    libtidy5deb1 \
    libxml2 \
    libxslt1.1 \
    libzip4 \
    nano \
    openssl \
    unzip \
    $BUILD_PACKAGES

# PHP Configuration
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install bz2
RUN docker-php-ext-install calendar
RUN docker-php-ext-install dba
RUN docker-php-ext-install curl
RUN docker-php-ext-install exif
RUN docker-php-ext-install gd
RUN docker-php-ext-install gettext
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
RUN docker-php-ext-install imap
RUN docker-php-ext-install intl
RUN docker-php-ext-install soap
RUN docker-php-ext-install tidy
RUN docker-php-ext-install xmlrpc
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install simplexml
RUN docker-php-ext-install xsl
RUN docker-php-ext-install zip
RUN docker-php-ext-configure hash --with-mhash

# MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Imagemagick
RUN pecl install imagick && docker-php-ext-enable imagick

# Apache Configuration
RUN a2enmod rewrite
RUN a2enmod headers

# SSL
RUN a2enmod ssl
RUN a2ensite default-ssl
RUN openssl req -subj '/CN=example.com/O=My Company Name LTD./C=US' -new -newkey rsa:2048 -days 365 -nodes -x509 -keyout /etc/ssl/private/ssl-cert-snakeoil.key -out /etc/ssl/certs/ssl-cert-snakeoil.pem

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Prestissimo
#RUN composer global require hirak/prestissimo --prefer-dist --no-progress --no-suggest --classmap-authoritative

# Node & NPM
RUN curl -sL https://deb.nodesource.com/setup_10.x  | bash -
RUN apt-get install -y --no-install-recommends nodejs
RUN curl -L https://npmjs.org/install.sh | sh

# Gulp & Yarn
RUN npm install -g gulp
RUN npm install -g yarn

# Config files
COPY docker/custom-php-conf.ini /usr/local/etc/php/conf.d/
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# We don't need this directory
RUN rm -R /var/www/html

# Purge (reduce package size)
RUN apt-get remove -qq --purge -y $BUILD_PACKAGES \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www
