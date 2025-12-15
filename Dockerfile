## Railway-friendly production image:
## - Builds Tailwind CSS during image build
## - Installs Composer deps inside the image
## - Runs nginx + php-fpm in a single container
## - Listens on $PORT (Railway provides it)

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

FROM php:8.4-fpm-bookworm AS app

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

ENV APP_ENV=prod \
    APP_DEBUG=0

WORKDIR /var/www/html

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
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
  && rm -f /etc/nginx/sites-enabled/default \
  && mkdir -p /var/www/html/var/cache /var/www/html/var/log \
  && chown -R www-data:www-data /var/www/html/var

EXPOSE 8080
CMD ["/entrypoint.sh"]