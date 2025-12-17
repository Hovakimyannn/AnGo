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
chmod -R a+rwX /var/www/html/var || true

# Ensure uploads/tmp dirs exist and are writable (EasyAdmin image uploads).
mkdir -p /var/www/html/var/tmp
mkdir -p /var/www/html/public/uploads/photos /var/www/html/public/uploads/posts /var/www/html/public/uploads/cache/photos
chown -R www-data:www-data /var/www/html/var/tmp /var/www/html/public/uploads
chmod -R a+rwX /var/www/html/var/tmp /var/www/html/public/uploads

# Ensure PHP's default session.save_path exists and is writable (do NOT change the path).
# This prevents login/CSRF issues when the default session directory is missing/not writable.
sess_save_path="$(php -r 'echo ini_get("session.save_path");' 2>/dev/null || true)"
sess_dir="${sess_save_path##*;}"
if [ -n "${sess_dir}" ] && [ "${sess_dir}" != "/tmp" ]; then
  mkdir -p "${sess_dir}"
  chmod 1733 "${sess_dir}" || true
fi

# Start PHP-FPM (background) + nginx (foreground)
php-fpm -D
exec nginx -g 'daemon off;'


