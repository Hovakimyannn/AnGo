# Connecting domain https://ango-beauty.am/

This guide covers connecting **ango-beauty.am** to your AnGo app on an Ubuntu server (Docker stack listening on port 8085).

## Overview

1. **DNS** – Point the domain to your server IP.
2. **Reverse proxy + SSL** – Nginx (or Caddy) on the host terminates HTTPS and forwards to Docker.
3. **App config** – Set `APP_URL` so emails and links use the correct domain.

---

## 1. DNS

At your domain registrar (where you manage ango-beauty.am), add:

| Type | Name | Value        | TTL  |
|------|------|--------------|------|
| A    | `@`  | YOUR_SERVER_IP | 300  |
| A    | `www`| YOUR_SERVER_IP | 300  |

Replace `YOUR_SERVER_IP` with the public IP of the server (e.g. the VPS where Docker runs).  
Optional: add a CNAME `www` → `ango-beauty.am` instead of a second A record.

Wait for DNS to propagate (from a few minutes up to 24–48 hours). Check with:

```bash
dig ango-beauty.am +short
dig www.ango-beauty.am +short
```

---

## 2. Reverse proxy and HTTPS on the server

Your app runs in Docker and is exposed on **port 8085**. You need a web server on the **host** that:

- Listens on ports 80 and 443 for `ango-beauty.am` (and optionally `www.ango-beauty.am`).
- Obtains an SSL certificate (e.g. Let’s Encrypt).
- Proxies requests to `http://127.0.0.1:8085`.

### Option A: Nginx on the host + Certbot (recommended)

**2.1 Install Nginx and Certbot**

```bash
sudo apt update
sudo apt install -y nginx certbot python3-certbot-nginx
```

**2.2 Create Nginx config for the domain**

Create a new config (e.g. `/etc/nginx/sites-available/ango-beauty.am`):

```nginx
# Redirect HTTP to HTTPS (after cert is in place you can uncomment)
# server {
#     listen 80;
#     server_name ango-beauty.am www.ango-beauty.am;
#     return 301 https://$host$request_uri;
# }

server {
    listen 80;
    server_name ango-beauty.am www.ango-beauty.am;

    location / {
        proxy_pass http://127.0.0.1:8085;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
    }
}
```

Enable the site and reload Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/ango-beauty.am /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

**2.3 Get SSL certificate**

```bash
sudo certbot --nginx -d ango-beauty.am -d www.ango-beauty.am
```

Follow the prompts. Certbot will adjust the Nginx config to serve HTTPS and (optionally) redirect HTTP → HTTPS.

**2.4 (Optional) Redirect www → apex**

If you want `https://www.ango-beauty.am` to redirect to `https://ango-beauty.am`, add a second server block and keep the proxy only on the main domain. Certbot often adds both; you can then edit the config so that the `www` block only does `return 301 https://ango-beauty.am$request_uri;`.

**2.5 Auto-renewal**

Certbot installs a cron/systemd timer. Test renewal:

```bash
sudo certbot renew --dry-run
```

---

### Option B: Caddy on the host (auto HTTPS)

Caddy obtains and renews Let’s Encrypt certificates automatically.

```bash
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sL 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sL 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install caddy
```

Create a Caddyfile (e.g. `/etc/caddy/Caddyfile`):

```
ango-beauty.am, www.ango-beauty.am {
    reverse_proxy 127.0.0.1:8085
}
```

Enable and start Caddy:

```bash
sudo systemctl enable caddy
sudo systemctl reload caddy
```

Caddy will request and renew certificates automatically.

---

### Option C: Docker nginx (server nginx-ը մնում է, միայն proxy է)

AnGo-ն աշխատում է **Docker nginx**-ում (8080); **server nginx**-ը 80-ում մնում է և միայն ango.beauty-ն proxy է անում Docker-ին (80 → 127.0.0.1:8080). Այդպես certbot-ի challenge-ը էլ կհասնի Docker nginx-ին։

**1. DNS**  
ango.beauty և www.ango.beauty → server IP.

**2. Server nginx-ում ավելացնել proxy**  
Սերվերում ավելացրեք site (մի server block), որը ango.beauty-ն ուղարկում է Docker-ին:

```bash
sudo cp /root/app/AnGo/nginx/production-reverse-proxy.conf.example /etc/nginx/sites-available/ango.beauty
sudo ln -s /etc/nginx/sites-available/ango.beauty /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

**3. Certbot webroot և stack**

```bash
cd ~/app/AnGo
mkdir -p certbot-webroot
docker compose up -d
```

**4. Let’s Encrypt certs**

```bash
sudo certbot certonly --webroot -w /root/app/AnGo/certbot-webroot -d ango.beauty -d www.ango.beauty
```

Request-ը գնում է ango.beauty:80 → server nginx → 127.0.0.1:8080 (Docker nginx) → `/.well-known/acme-challenge/` from certbot-webroot.

**5. Enable HTTPS in Docker nginx**

In `docker-compose.yml`, under the nginx service `volumes`, **uncomment**:

```yaml
# - ./nginx/default-ssl.conf:/etc/nginx/conf.d/default-ssl.conf:ro
# - /etc/letsencrypt:/etc/letsencrypt:ro
```

Then restart nginx:

```bash
docker compose up -d nginx
```

**6. (Optional) Redirect HTTP → HTTPS**  
In `nginx/default.conf`, you can add a `return 301 https://$host$request_uri;` inside the `listen 80` server block (e.g. in a new `location /` that returns the redirect), so that HTTP requests go to HTTPS.

**7. Renewal**  
Certbot on the host can renew with:

```bash
sudo certbot renew
```

After renewal, reload nginx in Docker:

```bash
docker compose exec nginx nginx -s reload
```

(Or add a cron that runs `certbot renew` and then `docker compose exec nginx nginx -s reload`.)

---

## 3. App configuration

On the server, set the public URL so links and emails use **https://ango-beauty.am/**.

**If you use a `.env` file** (e.g. in `~/app/AnGo` or inside the container’s app root):

```bash
APP_URL="https://ango-beauty.am"
```

If the app runs in Docker and reads env from `docker-compose`, add to the `php` service:

```yaml
environment:
  APP_URL: "https://ango-beauty.am"
  DATABASE_URL: "..."
  REDIS_URL: "..."
```

Then restart the app:

```bash
cd ~/app/AnGo
docker compose up -d php
# If you use Symfony cache, clear it
docker compose exec php php bin/console cache:clear --env=prod
```

**Trusted proxies**  
The project already uses `TRUSTED_PROXIES=REMOTE_ADDR`, so Symfony will trust `X-Forwarded-Proto` and similar headers from the reverse proxy. No change needed if you’re behind a single proxy.

---

## 4. Firewall

Allow HTTP and HTTPS on the host:

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp   # SSH
sudo ufw enable
sudo ufw status
```

Port **8085** does not need to be public; only Nginx/Caddy on the host (80/443) should be exposed.

---

## 5. Quick checklist

- [ ] DNS A (and/or CNAME) for `ango-beauty.am` and `www` → server IP.
- [ ] Either: (A) Nginx/Caddy on host proxying to Docker, or (C) Docker nginx on 80/443.
- [ ] SSL in place (Certbot or Caddy).
- [ ] `APP_URL=https://ango-beauty.am` set and app/container restarted.
- [ ] Firewall allows 80 and 443.

After that, **https://ango-beauty.am/** should load your AnGo app.
