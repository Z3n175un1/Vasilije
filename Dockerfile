# =========================================================
# ETAPA 1: FRONTEND
# PNPM + VITE
# =========================================================

FROM node:22-alpine AS frontend-builder

WORKDIR /app

# Activar Corepack para utilizar pnpm
RUN corepack enable

# Copiar únicamente archivos de dependencias
# para aprovechar el cache de Docker
COPY package.json pnpm-lock.yaml ./

# Instalar dependencias exactamente según el lockfile
RUN pnpm install --frozen-lockfile

# Copiar el proyecto completo
COPY . .

# Compilar assets de producción
# Esto genera:
# /app/public/build/manifest.json
# /app/public/build/assets/*
RUN pnpm run build


# =========================================================
# ETAPA 2: COMPOSER
# =========================================================

FROM composer:2 AS vendor

WORKDIR /app

# Copiar proyecto
COPY . .

# Instalar dependencias PHP
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

# Optimizar autoload
RUN composer dump-autoload --optimize


# =========================================================
# ETAPA 3: PHP / LARAVEL
# =========================================================

FROM php:8.3-fpm

# ---------------------------------------------------------
# Dependencias del sistema
# ---------------------------------------------------------

RUN apt-get update \
    && apt-get install -y \
        libpq-dev \
        unzip \
        zip \
    && rm -rf /var/lib/apt/lists/*


# ---------------------------------------------------------
# Extensiones PHP
# ---------------------------------------------------------

RUN docker-php-ext-install -j$(nproc) \
    pgsql \
    pdo_pgsql


# ---------------------------------------------------------
# Directorio de trabajo
# ---------------------------------------------------------

WORKDIR /app


# ---------------------------------------------------------
# Copiar aplicación Laravel
# ---------------------------------------------------------

COPY . .


# ---------------------------------------------------------
# Copiar dependencias Composer
# ---------------------------------------------------------

COPY --from=vendor /app/vendor ./vendor


# ---------------------------------------------------------
# COPIAR BUILD DE VITE
#
# Esto es lo que faltaba en tu Dockerfile anterior.
#
# El frontend-builder genera:
#
# public/build/
# ├── manifest.json
# └── assets/
#
# ---------------------------------------------------------

COPY --from=frontend-builder /app/public/build ./public/build


# ---------------------------------------------------------
# Directorios necesarios de Laravel
# ---------------------------------------------------------

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache


# ---------------------------------------------------------
# Permisos
# ---------------------------------------------------------

RUN chmod -R 775 storage bootstrap/cache


# ---------------------------------------------------------
# Descubrir paquetes Laravel
# ---------------------------------------------------------

RUN php artisan package:discover --ansi


# =========================================================
# VARIABLES DE PRODUCCIÓN
# =========================================================

ENV APP_ENV=production
ENV APP_DEBUG=false


# =========================================================
# PUERTO
# =========================================================

EXPOSE 10000


# =========================================================
# ARRANQUE
# =========================================================

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
