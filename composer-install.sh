#!/bin/sh
# Script de instalación de Composer con soporte de autenticación opcional.
# Acepta el secreto como JSON de COMPOSER_AUTH o como token plano de GitHub.
set -eu

SECRET_FILE="${1:-/run/secrets/composer_auth}"

if [ -s "$SECRET_FILE" ]; then
    secret=$(tr -d '\r\n\t ' < "$SECRET_FILE")
    if [ -n "$secret" ]; then
        case "$secret" in
            {*)
                export COMPOSER_AUTH="$secret"
                ;;
            *)
                export COMPOSER_AUTH='{"github-oauth":{"github.com":"'"$secret"'"}}'
                ;;
        esac
        echo "[composer-install] Autenticación configurada correctamente."
    fi
fi

exec composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-reqs
