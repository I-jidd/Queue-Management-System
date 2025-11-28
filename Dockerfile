FROM php:8.2-apache

# Install dependencies and PostgreSQL PDO extension
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy web root and supporting source files
COPY public/ html/
COPY src/ src/
COPY database/ database/

# Ensure Apache can read/write runtime files
RUN chown -R www-data:www-data /var/www/html /var/www/src

# Expose default Apache port
EXPOSE 80
