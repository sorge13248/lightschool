FROM php:8.5-cli-alpine

RUN apk add --no-cache git curl unzip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies at container start so the host vendor/ dir is used.
# Running composer install ensures dev deps (Pest, Guzzle) are present.
CMD ["sh", "-c", "composer install --no-interaction --quiet && vendor/bin/pest --colors=always"]
