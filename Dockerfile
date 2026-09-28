FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extension build dependencies
RUN apk add --no-cache \
    curl \
    git \
    libpq-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    su-exec

# Install PHP extensions required by Laravel and PostgreSQL
RUN docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    mbstring \
    intl \
    zip \
    bcmath \
    opcache \
    exif

# Copy Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP production settings
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=1'; \
    echo 'upload_max_filesize=10M'; \
    echo 'post_max_size=12M'; \
    echo 'memory_limit=256M'; \
} > /usr/local/etc/php/conf.d/production.ini

WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# Copy application source code
COPY . .

# Run post autoload dump and package discover
RUN composer dump-autoload --no-dev --optimize

# Ensure correct directories and permissions for storage and bootstrap/cache
RUN mkdir -p /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/storage/app/private \
             /var/www/html/storage/app/public \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
