FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    && rm -rf /var/lib/apt/lists/*

# Copy application
COPY . /var/www/html
WORKDIR /var/www/html

# Install Composer dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Nginx config
COPY nginx.conf /etc/nginx/sites-available/default

# Start services
CMD service nginx start && php-fpm
