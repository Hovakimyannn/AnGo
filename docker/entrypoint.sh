#!/usr/bin/env sh
set -eu

# Railway provides $PORT. Default makes local runs easier.
PORT="${PORT:-8080}"
export PORT

# Render nginx config with the correct listen port.
envsubst '$PORT' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Ensure Symfony writable dirs exist (cache/logs)
mkdir -p /var/www/html/var/cache /var/www/html/var/log
chown -R www-data:www-data /var/www/html/var

# Start PHP-FPM (background) + nginx (foreground)
php-fpm -D
exec nginx -g 'daemon off;'


