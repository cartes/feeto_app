# ETAPA 1: Dependencias de PHP (Composer)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Instalamos sin ejecutar scripts para evitar errores con 'artisan' ausente
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .
# Ahora que los archivos están presentes, generamos el autoload
RUN composer dump-autoload --optimize --no-dev

# ETAPA 2: Compilación de Frontend (Vite/Vue)
FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
# Importante: Copiamos vendor para que Vite encuentre a Ziggy si es necesario
COPY --from=vendor /app/vendor ./vendor
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY postcss.config.js ./
COPY tailwind.config.js ./
RUN npm run build

# ETAPA 3: Producción (PHP 8.5)
FROM php:8.5-fpm-bookworm AS runtime
WORKDIR /var/www/html

# Instalamos dependencias del sistema incluyendo libpq-dev para Postgres
RUN apt-get update && apt-get install -y \
    curl \
    git \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    nginx \
    supervisor \
    unzip \
    zip \
    && docker-php-ext-install bcmath intl pcntl pdo_mysql pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable opcache redis \
    && rm -rf /var/lib/apt/lists/*

# Copiamos archivos desde las etapas anteriores
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
