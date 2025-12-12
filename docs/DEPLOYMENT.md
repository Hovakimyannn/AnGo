# Deployment (Production)

## 1) Install PHP dependencies (no dev)

```bash
composer install --no-dev --optimize-autoloader
```

## 2) Build CSS (no Tailwind CDN)

```bash
npm ci
npm run build:css
```

Deploy the output:
- `public/build/app.css`

## 3) Warm up Symfony cache

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup
```

## 4) Nginx cache headers
This repo includes caching rules in `nginx/default.conf` for:
- `/uploads/*`
- common static file extensions (css/js/images/fonts)

Make sure your production nginx config includes similar rules.

## 5) Image optimization prerequisites
Uploads are optimized on save using GD (jpeg/webp). If you deploy using the repo Dockerfile, it installs GD with jpeg/webp support.

## 6) SEO indexing header
Public pages should be indexable; `/admin` and `/login` should be `noindex`.
Handled by `src/EventSubscriber/RobotsHeaderSubscriber.php`.

If you use a CDN/reverse-proxy, ensure it does **not** override `X-Robots-Tag` to `noindex`.


