# PHP-FPM for API Platform 7.2 (Symfony 7.x) - PHP 8.4
FROM php:8.4-fpm-alpine

# System deps
RUN apk add --no-cache     bash git unzip icu-dev libzip-dev oniguruma-dev     libpq-dev postgresql-client curl openssl

# PHP extensions
RUN docker-php-ext-configure intl  && docker-php-ext-install -j$(nproc)       intl opcache zip pdo pdo_pgsql

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first (use cache)
COPY composer.json composer.lock* symfony.lock* ./
RUN composer install --no-interaction --prefer-dist --no-progress || true

# Copy app
COPY . .

# Permissions for Symfony var/
RUN mkdir -p var \
 && chown -R www-data:www-data var

EXPOSE 9000
CMD ["php-fpm"]
