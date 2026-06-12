# ETAPA 1: Composer
FROM composer:2 AS composer

# ETAPA 2: Dependencias de PHP (Composer)
FROM php:8.5-cli-bookworm AS vendor
WORKDIR /app
COPY --from=composer /usr/bin/composer /usr/bin/composer

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && install-php-extensions bcmath gd intl mbstring pcntl pdo_mysql pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

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

# ETAPA 3: Compilación de Frontend (Vite/Vue)
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

# ETAPA 4: Producción (PHP 8.5)
FROM php:8.5-fpm-bookworm AS runtime
WORKDIR /var/www/html

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# Instalamos dependencias del sistema y la extensión redis.
RUN apt-get update && apt-get install -y \
    curl \
    git \
    nginx \
    supervisor \
    unzip \
    zip \
    && install-php-extensions bcmath gd intl mbstring pcntl pdo_mysql pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Compilamos phpredis con submódulos manualmente desde el commit compatible con PHP 8.5.
RUN apt-get update && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
    && git clone https://github.com/phpredis/phpredis.git /tmp/phpredis \
    && cd /tmp/phpredis \
    && git checkout 1e6f5477 \
    && git submodule update --init --recursive \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/phpredis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

RUN { \
        echo 'upload_max_filesize=8M'; \
        echo 'post_max_size=8M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# Copiamos archivos desde las etapas anteriores
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY container-entrypoint.sh /usr/local/bin/container-entrypoint.sh

RUN chmod +x /usr/local/bin/container-entrypoint.sh \
    && rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 80 8080
ENTRYPOINT ["/usr/local/bin/container-entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
