#!/usr/bin/env sh
set -eu

# Railway provides $PORT. Default makes local runs easier.
PORT="${PORT:-8080}"
export PORT

# Helpful log line for Railway startup logs
echo "Starting nginx+php-fpm (PORT=${PORT}, APP_ENV=${APP_ENV:-unset})"

# Render nginx config with the correct listen port.
envsubst '$PORT' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Ensure Symfony writable dirs exist (cache/logs)
mkdir -p /var/www/html/var/cache /var/www/html/var/log
chown -R www-data:www-data /var/www/html/var

# Ensure uploads/tmp dirs exist and are writable (EasyAdmin image uploads).
mkdir -p /var/www/html/var/tmp
mkdir -p /var/www/html/var/sessions
mkdir -p /var/www/html/public/uploads/photos /var/www/html/public/uploads/posts /var/www/html/public/uploads/cache/photos
chown -R www-data:www-data /var/www/html/var/tmp /var/www/html/var/sessions /var/www/html/public/uploads
chmod -R ug+rwX /var/www/html/var/tmp /var/www/html/var/sessions /var/www/html/public/uploads

# Start PHP-FPM (background) + nginx (foreground)
php-fpm -D
exec nginx -g 'daemon off;'


