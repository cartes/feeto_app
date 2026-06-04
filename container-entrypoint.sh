#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 777 storage bootstrap/cache || true

rm -rf public/storage
php artisan storage:link || true
php artisan optimize

exec "$@"
