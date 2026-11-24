# syntax=docker/dockerfile:1
# Build context is the repository root, so stages can reach both backend/ and frontend/.

# ---------- Backend ----------
FROM php:8.4-fpm-alpine AS php-base

WORKDIR /var/www

RUN apk add --no-cache $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring pdo_mysql zip opcache \
    && apk del $PHPIZE_DEPS

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-laravel.ini

# Composer is isolated to this stage. The PHP-FPM image below does not contain it.
FROM php-base AS composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY backend/composer.json backend/composer.lock /var/www/
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY backend /var/www
RUN composer dump-autoload --optimize

FROM php-base AS php-fpm
# backend/ is bind-mounted, so give www-data the host user's ids: files written by PHP and by
# `docker compose exec app ...` stay editable on the host, and host-created files stay writable by PHP.
# Override with --build-arg UID=$(id -u) --build-arg GID=$(id -g) if your host user is not 1000.
ARG UID=1000
ARG GID=1000
RUN sed -i "s/^www-data:x:82:82:/www-data:x:${UID}:${GID}:/" /etc/passwd \
    && sed -i "s/^www-data:x:82:/www-data:x:${GID}:/" /etc/group \
    && mkdir -p /home/www-data && chown www-data:www-data /home/www-data
COPY backend /var/www
COPY --from=composer /var/www/vendor /var/www/vendor
RUN chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
USER www-data

EXPOSE 9000
CMD ["php-fpm"]

# Serves backend/public and passes PHP requests to the php-fpm service.
FROM nginx:1.27-alpine AS nginx
COPY docker/nginx/backend.conf /etc/nginx/conf.d/default.conf
COPY backend/public /var/www/public
EXPOSE 80

# ---------- Frontend ----------
FROM node:22-alpine AS frontend-build
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend ./
RUN npm run build

# Production frontend: static Vue build, with /api proxied to the backend nginx.
FROM nginx:1.27-alpine AS frontend
COPY docker/nginx/frontend.conf /etc/nginx/conf.d/default.conf
COPY --from=frontend-build /app/dist /usr/share/nginx/html
EXPOSE 80
