# syntax=docker/dockerfile:1.7
# ---------------------------------------------------------------
# API PHP (Apache + mod_php) — imagen multi-etapa
# ---------------------------------------------------------------

# 1) Dependencias de Composer (sin herramientas de desarrollo)
FROM composer:2.10 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-reqs

# 2) Imagen de ejecución
FROM php:8.5.12-apache AS runtime
LABEL org.opencontainers.image.title="tutorias-api" \
      org.opencontainers.image.description="API REST del Sistema de Tutorías UNIMINUTO"

RUN docker-php-ext-install pdo_mysql opcache \
 && a2enmod rewrite headers remoteip \
 && apt-get update && apt-get install -y --no-install-recommends curl \
 && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-tutorias.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/app
COPY --from=vendor /app/vendor ./vendor
COPY bin ./bin
COPY config ./config
COPY database ./database
COPY public ./public
COPY src ./src
COPY composer.json ./

RUN mkdir -p /var/www/app/var/cache && chown -R www-data:www-data /var/www/app/var \
 && chmod +x bin/console

ENV APP_ENV=production \
    CACHE_DIR=/var/www/app/var/cache \
    LOG_STREAM=php://stderr

HEALTHCHECK --interval=15s --timeout=3s --start-period=20s --retries=3 \
  CMD curl -fsS http://localhost/health || exit 1

EXPOSE 80
