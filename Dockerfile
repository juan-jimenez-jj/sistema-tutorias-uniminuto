# syntax=docker/dockerfile:1.7
# =====================================================================
# Imagen única para la nube (Railway, Render, cualquier host con Docker):
# interfaz web compilada + API PHP + worker de notificaciones en un solo
# contenedor, detrás de Apache. La base de datos es un servicio MySQL aparte
# que se conecta con la variable MYSQL_URL.
# Para el entorno local completo usa docker-compose.yml.
# =====================================================================

FROM node:22-alpine AS web
WORKDIR /web
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY frontend/ ./
ARG VITE_DEMO=1
ENV VITE_DEMO=$VITE_DEMO
RUN npm run build

FROM composer:2.8 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock* ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-reqs

FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql opcache \
 && a2enmod rewrite headers remoteip \
 && rm -rf /var/lib/apt/lists/*

COPY backend/docker/php.ini /usr/local/etc/php/conf.d/zz-tutorias.ini
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/app
COPY --from=vendor /app/vendor ./vendor
COPY backend/bin ./bin
COPY backend/config ./config
COPY backend/database ./database
COPY backend/public ./public
COPY backend/src ./src
COPY backend/composer.json ./
COPY --from=web /web/dist ./web
COPY deploy/server.php ./web/server.php
COPY deploy/entrypoint.sh /usr/local/bin/tutorias-start

RUN chmod +x /usr/local/bin/tutorias-start bin/console \
 && mkdir -p var/cache && chown -R www-data:www-data var

ENV APP_ENV=production \
    COOKIE_SECURE=true \
    APP_HSTS=true \
    MAIL_DRIVER=log \
    CACHE_DIR=/var/www/app/var/cache \
    LOG_STREAM=php://stderr

EXPOSE 8080
CMD ["tutorias-start"]
