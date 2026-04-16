FROM php:8.5-fpm-alpine

RUN apk add --no-cache libsodium-dev gmp-dev oniguruma-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring gmp bcmath fileinfo zip pcntl \
    # Add www-data to the root supplementary group so FPM workers can traverse
    # bind-mounted project directories (drwxr-x--- root:root on the host fs).
    # The pool keeps group=www-data, bypassing FPM's "no root group" check.
    && adduser www-data root

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/docker-local.ini
