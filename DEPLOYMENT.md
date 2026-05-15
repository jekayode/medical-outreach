# Deployment guide — on-site mini-PC (medical outreach)

This matches **PRD §1.3–1.4** (Ubuntu Server, Nginx, PHP-FPM, MySQL 8, LAN) and **PRD §14 item 23**. Adjust paths and hostnames to your venue.

## 1. Server baseline

- **OS:** Ubuntu Server 24.04 LTS (x86_64 or ARM64 mini-PC).
- **Roles:** Application + MySQL on the same machine is fine for Phase 1; use a **second disk or USB/NAS mount** for database dumps (PRD §12.2).
- **Network:** Static IP on the LAN (e.g. `192.168.50.1`). Tablets join the outreach Wi-Fi and open `http://<server-ip>/`.

HTTPS on the LAN is optional per PRD; use **HTTP** only if you accept browser “not secure” warnings on tablets.

## 2. System packages

Install PHP (8.4+), extensions commonly required by Laravel + Filament, Nginx, MySQL client/server tools, Node (for building assets), Git, and `mysqldump` (usually in `mysql-client`):

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml php8.4-mbstring \
  php8.4-curl php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd php8.4-sqlite3 unzip git curl \
  mysql-client
```

Install **Node.js LTS** (e.g. via [NodeSource](https://github.com/nodesource/distributions) or your preferred method) so you can run `npm ci` / `npm run build` on the server.

## 3. MySQL

1. Secure installation (set root password, remove test DB) if you have not already:

   ```bash
   sudo mysql_secure_installation
   ```

2. Create database and user (example):

   ```sql
   CREATE DATABASE medical_outreach CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'medical'@'localhost' IDENTIFIED BY 'use-a-long-random-password';
   GRANT ALL ON medical_outreach.* TO 'medical'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. Ensure **InnoDB** and sane defaults; enable binary log only if you need point-in-time recovery (optional for Phase 1).

## 4. Application directory and permissions

Example app root: `/var/www/medical` (owned by deploy user; web server needs read + write to `storage` and `bootstrap/cache`).

```bash
sudo mkdir -p /var/www/medical
sudo chown -R $USER:www-data /var/www/medical
# After code is present:
cd /var/www/medical
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
sudo chown -R www-data:www-data storage bootstrap/cache
```

Deploy code with **Git**, **rsync**, or your release pipeline. On the server:

```bash
cd /var/www/medical
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit **`.env`** (at minimum):

| Variable | Notes |
|----------|--------|
| `APP_NAME` | Organisation name |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `http://192.168.x.x` (LAN URL tablets use) |
| `DB_*` | Point at `medical_outreach` and the DB user above |
| `SESSION_DOMAIN` | Usually **leave unset** for IP access, or set if you use a hostname |
| `CACHE_STORE` / `SESSION_DRIVER` | `file` or `database` is fine on a single node |

Build frontend assets:

```bash
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
```

Seed roles/users only if you intend to (e.g. first admin); do not run demo seeders on a production outreach box unless planned.

## 5. PHP-FPM

- Use the default pool (e.g. `www.conf`) or a dedicated pool for this site.
- Set `user` / `group` to `www-data` (or match Nginx).
- Tune `pm.max_children` for RAM (mini-PCs are often 4–16 GB); start conservative (e.g. 10–20) and watch `php-fpm` slow logs.

Restart after PHP or pool changes:

```bash
sudo systemctl restart php8.4-fpm
```

(Adjust version if your `php-fpm` package differs.)

## 6. Nginx

Point the document root to Laravel’s **`public/`** directory. Example server block (replace `server_name` and `root`):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /var/www/medical/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site, test config, reload:

```bash
sudo ln -s /etc/nginx/sites-available/medical /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 7. Scheduler and backups (Laravel)

This app registers an **hourly** `database:backup` task (MySQL only). The OS must run Laravel’s scheduler every minute.

Add a **cron** entry for the web/deploy user (often `www-data` if the project is owned by `www-data`):

```cron
* * * * * cd /var/www/medical && php artisan schedule:run >> /dev/null 2>&1
```

Backups are written under **`storage/backups/`** as gzipped SQL dumps. For PRD §12.2:

- Mount a **second disk** (or NFS/SMB share) and either symlink `storage/backups` to that mount, or use `rsync`/cron to copy dumps off the system disk after each event.

`mysqldump` must be on `PATH` for the user running `php artisan database:backup`.

## 8. Filament admin (`/admin`)

- Only users with the **`admin`** role and Filament access should use `/admin`.
- After deploy, create the first admin via `php artisan db:seed` (if you provide a seeder), Tinker, or a one-off invite flow—follow your organisation’s policy.

## 9. Day-of-outreach checklist

1. Router Wi-Fi up; server reachable from a tablet browser.
2. `php artisan config:cache` and `php artisan route:cache` after any `.env` change (optional `view:cache`).
3. One outreach marked **active** in admin; date range covers today.
4. Spot-check: check-in slip print, one station login per role, QR scan.
5. Confirm cron is firing: `grep CRON /var/log/syslog` or temporarily log `schedule:run` output.

## 10. After the event

- Copy **`storage/backups/`** (and optionally full `mysqldump` off-box) to your **cloud reporting** environment per PRD.
- For cloud copies, serve the app **only over HTTPS** (PRD §12.3).

## 11. Troubleshooting

| Symptom | Things to check |
|--------|-------------------|
| 502 from Nginx | PHP-FPM socket path/version; `sudo journalctl -u php8.4-fpm -n 50` |
| 500 from Laravel | `storage/logs/laravel.log`, file permissions on `storage/` |
| Blank CSS/JS | Run `npm run build`; `APP_URL` matches how users open the site |
| DB backup skipped | `DB_CONNECTION=mysql`; `mysqldump` installed; credentials in `.env` |
| Tablets cannot connect | Firewall (`ufw allow 80/tcp`), Wi-Fi VLAN, wrong IP |

## 12. Laravel Cloud

[Laravel Cloud](https://cloud.laravel.com/) is the recommended hosted option for this app. New environments default to **PHP 8.5**, which **breaks the build** because **Maatwebsite Excel** depends on **PhpSpreadsheet 1.x**, which requires **`php < 8.5.0`**.

### Fix the build error (`phpoffice/phpspreadsheet` / PHP 8.5)

1. In the Laravel Cloud dashboard, open your environment → **General settings** → **PHP version** → select **8.4** (not 8.5). Redeploy.  
   Docs: [Environments — PHP version](https://cloud.laravel.com/docs/environments#php-version)

2. This repo pins Composer to **`>=8.4.0 <8.5.0`** so `composer install` fails fast if the platform uses 8.5.

### Suggested build / deploy commands

**Build** (example):

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Deploy** (example):

```bash
php artisan migrate --force --no-interaction
```

Do **not** run `optimize:clear` on deploy (Laravel Cloud advises against it). Queue workers restart automatically after deploy.

### Environment variables

| Variable | Notes |
|----------|--------|
| `APP_KEY` | Required |
| `APP_URL` | Your `*.laravel.cloud` or custom domain |
| `QUEUE_CONNECTION` | `database` (unless you attach Redis and configure it) |
| `CACHE_STORE` | `database` or a Cloud KV store |
| `LOG_CHANNEL` | `stderr` or Cloud’s logging stack |

Extensions **intl**, **zip**, and **gd** are available on Laravel Cloud (required for Filament and Excel).

---

## 13. Railway (GitHub deploy)

Railway’s current PHP builder is **[Railpack](https://railpack.com/languages/php)** (FrankenPHP + Caddy, document root `public/`). This repo includes **`railway.toml`** so the service uses **Railpack** explicitly and health checks **`/up`**.

### PHP version (Symfony 8 / Laravel 13)

`composer.json` requires **PHP 8.4.x** (`>=8.4.0 <8.5.0`). Symfony Console v8 needs PHP **8.4+**; PhpSpreadsheet needs a version **below 8.5**. Railpack reads Composer when possible—**do not** pin the service to PHP 8.3 in the dashboard if it overrides Composer.

### Filament / `ext-intl`

1. **`composer.json` requires `"ext-intl"`, `"ext-zip"`, and `"ext-gd"`** — Railpack installs PHP extensions declared there (needed by Filament / OpenSpout and Maatwebsite Excel / PhpSpreadsheet). Commit and push so the lock file matches.
2. If a build still reports missing extensions, set a Railway variable (service **Variables**):
   - **`RAILPACK_PHP_EXTENSIONS=intl,zip,gd`**  
   (Adjust if you add more, e.g. `intl,zip,gd,redis`.)

### Avoid a bare root `Dockerfile`

If a **`Dockerfile`** exists at the repository root, **Railway will use it instead of Railpack**. A minimal PHP‑CLI image has no FrankenPHP/Caddy and is the wrong fit for a normal web service. Prefer **no root Dockerfile** (or a complete production image + custom start) so **`railway.toml`** + Railpack apply.

### Recommended Railway variables

| Variable | Purpose |
|----------|---------|
| `APP_KEY` | From `php artisan key:generate` (base64) |
| `APP_URL` | Your Railway public URL (e.g. `https://your-app.up.railway.app`) |
| `DATABASE_URL` | From Railway MySQL/Postgres plugin, or compose `DB_*` manually |
| `LOG_CHANNEL` | `stderr` so logs show in Railway ([docs](https://docs.railway.com/guides/laravel#logging)) |
| `RAILPACK_PHP_EXTENSIONS` | Fallback: `intl,zip,gd` if Railpack skips any extension from Composer |
| `RAILPACK_SKIP_MIGRATIONS` | `true` — migrations run only in **`railway/init-app.sh`** (pre-deploy), not on every container boot |

Optional: `LOG_STDERR_FORMATTER=\Monolog\Formatter\JsonFormatter` for structured logs.

**Do not run `php artisan migrate` in both pre-deploy and a custom start command** (`start.sh` no longer migrates). Running migrate twice causes `Table 'permissions' already exists` (SQLSTATE 42S01).

If the database is already in a bad state (tables exist but `migrations` is out of sync), use Railway’s shell / one-off command:

```bash
php artisan migrate:status
```

On a **throwaway** staging database you can reset with `php artisan migrate:fresh --force` (deletes all data). On production, fix the `migrations` table or restore from backup instead of `fresh`.

### Scheduler on Railway

Laravel’s scheduler needs a **separate cron service** or Railway **Cron** job running `php artisan schedule:run` every minute (see [Railway Laravel guide](https://docs.railway.com/guides/laravel)). The app service alone does not run cron.

This repo includes **`railway/`** helper scripts (executable):

| Script | Purpose |
|--------|---------|
| `railway/init-app.sh` | Migrations + `optimize:clear` + config/event/route/view cache (use as **Pre-Deploy Command** on the app service, e.g. `chmod +x ./railway/init-app.sh && sh ./railway/init-app.sh`) |
| `railway/run-worker.sh` | `php artisan queue:work` (**Custom Start Command** on a worker service) |
| `railway/run-cron.sh` | Runs `schedule:run` every 60s in a loop (**Custom Start Command** on a cron service) |

Set these on **app, worker, and cron** services (Railway’s Redis plugin often sets `QUEUE_CONNECTION=redis` and `REDIS_CLIENT=predis`, which breaks the worker unless you use Redis everywhere):

| Variable | Recommended value |
|----------|-------------------|
| `QUEUE_CONNECTION` | `database` |
| `CACHE_STORE` | `database` |

`railway/run-worker.sh` runs `queue:work database` explicitly. The app also ships **`predis/predis`** if you intentionally use Redis with `REDIS_CLIENT=predis`.

---

*Phase 1E in the PRD (dry run, staff one-pagers) is process and training, not additional application code.*
