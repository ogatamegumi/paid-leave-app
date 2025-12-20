FROM php:8.4-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
 && docker-php-ext-install pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN chmod -R 775 storage bootstrap/cache

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
