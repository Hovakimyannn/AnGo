# Development

## Requirements
- PHP 8.2 (or Docker)
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


