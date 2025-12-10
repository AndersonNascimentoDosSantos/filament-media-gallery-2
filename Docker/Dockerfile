ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli
 
# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libmagickwand-dev \
    sqlite3 \
    libsqlite3-dev \
    ffmpeg \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip \
    && pecl install imagick \
    && pecl install xdebug \
    && docker-php-ext-enable imagick \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Git safe directory
RUN git config --global --add safe.directory /app

# Set working directory
WORKDIR /app

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1
# Configure Xdebug to be off by default (improves performance)
# It will be activated on-demand by setting XDEBUG_MODE=coverage
ENV XDEBUG_MODE=off

CMD ["vendor/bin/pest"]
