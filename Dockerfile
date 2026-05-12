FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-dev

FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY postcss.config.js ./

RUN npm run build

FROM php:8.3-fpm-bookworm AS runtime

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    curl \
    git \
    libicu-dev \
    libzip-dev \
    nginx \
    supervisor \
    unzip \
    zip \
    && docker-php-ext-install bcmath intl opcache pcntl pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY container-entrypoint.sh /usr/local/bin/container-entrypoint.sh

RUN chmod +x /usr/local/bin/container-entrypoint.sh \
    && rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80 8080

ENTRYPOINT ["/usr/local/bin/container-entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
