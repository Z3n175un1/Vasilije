# Stage 1: Build frontend assets
FROM node:22-slim AS node-build
WORKDIR /build
COPY package.json vite.config.js ./
COPY resources/ resources/
RUN npm install && npm run build

# Stage 2: Build PHP dependencies
FROM php:8.3-fpm-alpine AS composer-build
RUN apk add --no-cache postgresql-dev libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# Stage 3: Final runtime image
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

WORKDIR /var/www

COPY --from=composer-build /build/vendor vendor/
COPY --from=node-build /build/public/build public/build/
COPY . .

RUN php artisan key:generate --force \
    && php artisan package:discover --ansi \
    && php artisan optimize

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
