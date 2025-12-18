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

# Uploads persistence:
# - On container platforms (like Railway), the filesystem is ephemeral across deploys.
# - Mount a persistent volume and either:
#   a) mount it directly to /var/www/html/public/uploads (recommended), OR
#   b) mount it elsewhere and set UPLOADS_DIR to that mount path.
DEFAULT_UPLOADS_DIR="/var/www/html/public/uploads"
UPLOADS_DIR="${UPLOADS_DIR:-$DEFAULT_UPLOADS_DIR}"
if [ "${UPLOADS_DIR}" != "${DEFAULT_UPLOADS_DIR}" ]; then
  echo "Uploads: using UPLOADS_DIR=${UPLOADS_DIR} (symlinked from ${DEFAULT_UPLOADS_DIR})"
  mkdir -p "${UPLOADS_DIR}"
  rm -rf "${DEFAULT_UPLOADS_DIR}"
  ln -s "${UPLOADS_DIR}" "${DEFAULT_UPLOADS_DIR}"
fi

# Ensure uploads/tmp dirs exist and are writable (EasyAdmin image uploads).
mkdir -p /var/www/html/var/tmp
mkdir -p /var/www/html/public/uploads/photos /var/www/html/public/uploads/posts /var/www/html/public/uploads/cache/photos
chown -R www-data:www-data /var/www/html/var/tmp /var/www/html/public/uploads
chmod -R a+rwX /var/www/html/var/tmp /var/www/html/public/uploads

# If uploads are stored on a mounted volume, it may start empty and hide the repo's files.
# Seed the branding logo into uploads if it's missing so emails/header don't break.
if [ ! -f /var/www/html/public/uploads/photos/ango-logo.png ] && [ -f /var/www/html/public/branding/ango-logo.png ]; then
  cp -f /var/www/html/public/branding/ango-logo.png /var/www/html/public/uploads/photos/ango-logo.png || true
  chmod a+rw /var/www/html/public/uploads/photos/ango-logo.png || true
fi

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


