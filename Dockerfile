# ---------- ETAPA 1: Frontend (placeholder) ----------
FROM alpine:3.18 AS frontend-builder
RUN mkdir -p /app/public/build


# ---------- ETAPA 2: Composer ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

RUN composer dump-autoload --optimize


# ---------- ETAPA 3: PHP (Debian) ----------
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo pdo_pgsql mbstring bcmath gd zip

WORKDIR /app

COPY . .

COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend-builder /app/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

RUN php artisan package:discover --ansi

ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 10000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
