# Development

## Requirements
- PHP 8.4 (or Docker)
- Node.js + npm (for CSS build)

## Run with Docker

```bash
docker compose up -d --build
```

App: `http://localhost:8085`

## PHP dependencies

```bash
composer install
```

## Email (SendGrid)

This app uses **Symfony Mailer**.

Recommended (SendGrid **API transport** — avoids SMTP port blocks/timeouts):

```bash
MAILER_DSN="sendgrid+api://YOUR_SENDGRID_API_KEY@default"
MAILER_FROM="AnGo <verified-sender@yourdomain.com>"
```

SMTP fallback (if your network/provider allows outbound SMTP):

```bash
MAILER_DSN="smtp://apikey:YOUR_SENDGRID_API_KEY@smtp.sendgrid.net:587"
MAILER_FROM="AnGo <verified-sender@yourdomain.com>"
```

Then test:

```bash
php bin/console app:send-test-email you@example.com
```

Notes:
- If you see `Connection to "smtp.sendgrid.net:587" timed out`, switch to `sendgrid+api://...` (recommended) or try SMTP port `2525`.
- `MAILER_FROM` must be a **SendGrid verified** sender (Single Sender Verification or Domain Authentication).
- To disable sending locally, use: `MAILER_DSN="null://null"`.

## Tailwind CSS (required for styles)
This project serves Tailwind as **compiled CSS** (`public/build/app.css`).

### One-time build

```bash
npm install
npm run build:css
```

### Watch mode (recommended)

```bash
npm run watch:css
```

## Symfony cache

```bash
php bin/console cache:clear
```


