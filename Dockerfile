## Railway-friendly production image:
## - Builds Tailwind CSS during image build
## - Installs Composer deps inside the image
## - Runs nginx + php-fpm in a single container
## - Listens on $PORT (Railway provides it)
##
## Important: We run PHPUnit during the image build (in a separate build stage).
## If tests fail, the image build fails and Railway won't deploy.

# syntax=docker/dockerfile:1.6

FROM node:20-alpine AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY tailwind.config.js postcss.config.js ./
COPY assets ./assets
COPY templates ./templates
COPY src ./src

RUN mkdir -p public/build \
  && npm run build:css

FROM php:8.4-fpm-bookworm AS base

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git \
    unzip \
    gettext-base \
    nginx \
    libzip-dev \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-jpeg --with-webp \
  && docker-php-ext-install -j"$(nproc)" \
    gd \
    opcache \
    pdo \
    pdo_pgsql \
    pgsql \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS test

ENV APP_ENV=test \
    APP_DEBUG=0

COPY composer.json composer.lock symfony.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

COPY . .
COPY --from=assets /app/public/build/ public/build/

# Symfony Runtime loads ".env" by default. In CI/build we may not have it committed.
RUN if [ ! -f .env ] && [ -f env.example ]; then cp env.example .env; fi

# Run tests during build; create a marker file to force this stage to be built for prod image.
RUN mkdir -p /var/www/html/var/sessions/test \
  && php bin/phpunit --colors=never --fail-on-phpunit-notice \
  && echo "ok" > /tmp/tests-passed

FROM base AS app

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY composer.json composer.lock symfony.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

COPY . .
COPY --from=assets /app/public/build/ public/build/

# Symfony Runtime loads ".env" by default. On Railway (git deploy), ".env" is often not committed.
# Provide a safe fallback from env.example so the app/console don't crash at boot.
RUN if [ ! -f .env ] && [ -f env.example ]; then cp env.example .env; fi

COPY nginx/railway.conf.template /etc/nginx/conf.d/default.conf.template
COPY docker/php-uploads.ini /usr/local/etc/php/conf.d/zzz-uploads.ini
COPY docker/entrypoint.sh /entrypoint.sh
COPY --from=test /tmp/tests-passed /tmp/tests-passed
RUN chmod +x /entrypoint.sh \
  && rm -f /etc/nginx/sites-enabled/default \
  && mkdir -p /var/www/html/var/cache /var/www/html/var/log \
  && chown -R www-data:www-data /var/www/html/var \
  # Make var/ writable even if the platform runs the container as a non-www-data user.
  && chmod -R a+rwX /var/www/html/var

EXPOSE 8080
CMD ["/entrypoint.sh"]
