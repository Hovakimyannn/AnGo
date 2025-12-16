# Deployment (Production)

## Railway (Dockerfile) — recommended

This repo includes a Railway-friendly `Dockerfile` that:
- builds Tailwind CSS during image build
- installs Composer deps in the image
- runs **nginx + php-fpm** in a single container
- listens on Railway's `$PORT`
- generates a safe `.env` from `env.example` (this repo ignores `.env` in git, but Symfony Runtime expects it to exist)

### Required Railway variables
- `APP_ENV=prod`
- `APP_DEBUG=0`
- `APP_SECRET` (generate a random 32+ char string)
- `DATABASE_URL` (from Railway Postgres)
- `MAILER_DSN` (SendGrid DSN)
- `MAILER_FROM` (verified sender)

SendGrid (recommended: **API transport** — avoids SMTP port blocks/timeouts):

```bash
MAILER_DSN="sendgrid+api://YOUR_SENDGRID_API_KEY@default"
MAILER_FROM="AnGo <verified-sender@yourdomain.com>"
```

SMTP fallback (if your network/provider allows outbound SMTP):

```bash
MAILER_DSN="smtp://apikey:YOUR_SENDGRID_API_KEY@smtp.sendgrid.net:587"
MAILER_FROM="AnGo <verified-sender@yourdomain.com>"
```

Example `DATABASE_URL` format:

```bash
postgresql://USER:PASSWORD@HOST:PORT/DB?serverVersion=16&charset=utf8
```

### Database migrations
After deploy (or on every deploy), run:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### Notes
- `public/uploads/*` is **ephemeral** on most container platforms. For persistent uploads, use object storage (S3/R2/etc).
- SendGrid requires a **verified sender** (Single Sender Verification or Domain Authentication). If not verified, SendGrid may reject emails.

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


