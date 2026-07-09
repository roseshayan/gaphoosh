# راه‌اندازی گپ‌هوش روی Ubuntu 24.04

این راهنما برای یک سرور Ubuntu 24.04 با Nginx، PHP-FPM، MySQL و Cloudflare نوشته شده است.

## پیش‌نیازها

- دامنه: `gaphoosh.ir`
- سرور Ubuntu 24.04
- دسترسی SSH با کاربر sudo
- ریپو GitHub پروژه
- بدون VPN روی سرور برای اتصال Dahl، مگر اینکه diagnose سبز باشد

## 1) آپدیت سرور

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git unzip curl ca-certificates ufw
```

## 2) نصب Nginx، MySQL، PHP-FPM و extensionهای لازم

```bash
sudo apt install -y nginx mysql-server \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip php8.3-intl php8.3-opcache
```

## 3) نصب Composer

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

## 4) نصب Node.js LTS

برای build کردن assetها نیاز به Node و NPM داری. پیشنهاد ساده روی سرور:

```bash
sudo apt install -y nodejs npm
node -v
npm -v
```

اگر نسخه Node مخزن Ubuntu برای build کافی نبود، روی سیستم خودت `npm run build` بزن و فایل‌های build شده را deploy کن، یا Node LTS را از منبع رسمی NodeSource نصب کن.

## 5) ساخت دیتابیس

```bash
sudo mysql
```

داخل MySQL:

```sql
CREATE DATABASE gaphoosh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gaphoosh_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON gaphoosh.* TO 'gaphoosh_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 6) دریافت پروژه از GitHub

```bash
sudo mkdir -p /var/www
sudo chown -R $USER:www-data /var/www
cd /var/www
git clone https://github.com/roseshayan/gaphoosh.git gaphoosh
cd gaphoosh
```

اگر نام ریپو را چیز دیگری گذاشتی، URL را عوض کن.

## 7) نصب dependencyها و build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## 8) ساخت فایل env production

```bash
cp .env.example .env
nano .env
```

نمونه production:

```env
APP_NAME="گپ‌هوش"
APP_URL="https://gaphoosh.ir"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE="Asia/Tehran"

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gaphoosh
DB_USERNAME=gaphoosh_user
DB_PASSWORD="CHANGE_THIS_STRONG_PASSWORD"

DAHL_BASE_URL="https://inference.dahl.global/v1"
DAHL_TOKEN_URL="https://inference.dahl.global/tokens"
DAHL_AUTH_MODE=auto
DAHL_API_KEY=""
DAHL_AUTO_TOKEN_ON_AUTH_ERROR=true
DAHL_DEFAULT_MODEL="MiniMaxAI/MiniMax-M2.7"
DAHL_STREAM_FALLBACK=true

MAX_DAILY_MESSAGES=80
MAX_INPUT_CHARS=12000
HISTORY_LIMIT=24
STREAM_TIMEOUT_SECONDS=180

ADMIN_MOBILE="09xxxxxxxxx"
ADMIN_PASSWORD="CHANGE_THIS_ADMIN_PASSWORD"
FIRST_REGISTERED_USER_IS_ADMIN=false

CONTACT_EMAIL="namayandeshayan@gmail.com"
CONTACT_TELEGRAM="https://t.me/SudoShayanNA"
CONTACT_GITHUB="https://github.com/roseshayan"
```

## 9) ساخت جدول‌ها و ادمین

```bash
php setup.php
php make-admin.php 09xxxxxxxxx
```

## 10) مجوز فایل‌ها

```bash
sudo chown -R www-data:www-data /var/www/gaphoosh/storage
sudo find /var/www/gaphoosh/storage -type d -exec chmod 775 {} \;
sudo find /var/www/gaphoosh/storage -type f -exec chmod 664 {} \;
```

## 11) تنظیم Nginx

فایل زیر را بساز:

```bash
sudo nano /etc/nginx/sites-available/gaphoosh.ir
```

محتوا:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name gaphoosh.ir www.gaphoosh.ir;
    root /var/www/gaphoosh/public;
    index index.php index.html;

    charset utf-8;
    client_max_body_size 8M;

    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /api/chat/stream {
        try_files $uri /index.php?$query_string;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        include fastcgi_params;
        fastcgi_buffering off;
        fastcgi_request_buffering off;
        fastcgi_read_timeout 240s;
        fastcgi_send_timeout 240s;
        proxy_buffering off;
        gzip off;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_read_timeout 240s;
    }

    location ~ /(?:app|database|storage|tools|scripts|vendor|node_modules)/ {
        deny all;
    }

    location ~ /\.(?:env|git|lock|json|md)$ {
        deny all;
    }

    location ~* \.(?:css|js|png|jpg|jpeg|gif|webp|svg|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, max-age=2592000, immutable";
        try_files $uri =404;
    }
}
```

فعال‌سازی:

```bash
sudo ln -s /etc/nginx/sites-available/gaphoosh.ir /etc/nginx/sites-enabled/gaphoosh.ir
sudo nginx -t
sudo systemctl reload nginx
```

## 12) فایروال

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

## 13) تست Dahl روی سرور

```bash
cd /var/www/gaphoosh
sudo -u www-data php tools/dahl-diagnose.php
```

قبل از انتشار، این بخش‌ها باید ok باشند:

- `models_public.ok = true`
- `token_creation.ok = true`
- `chat_test.ok = true`

اگر Cloudflare challenge دیدی، مشکل از مسیر شبکه سرور به Dahl است، نه از Nginx خودت.

## 14) Cloudflare برای gaphoosh.ir

در Cloudflare:

1. DNS دامنه را به IP سرور بزن.
2. Proxy را روشن کن، ابر نارنجی.
3. SSL/TLS را روی **Full (strict)** بگذار.
4. برای origin یک certificate معتبر داشته باش: Let's Encrypt یا Cloudflare Origin Certificate.
5. Cache Rule بگذار که این مسیرها cache نشوند:
   - `/api/*`
   - `/chat*`
   - `/admin*`
   - `/login*`
   - `/register*`
6. برای assetها cache خوب است:
   - `/assets/*`

## 15) SSL روی origin

اگر Cloudflare Origin Certificate می‌گیری، cert و key را روی سرور بگذار و server block 443 بساز. اگر Let's Encrypt می‌خواهی، اول proxy را موقتاً DNS only کن و بعد:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d gaphoosh.ir -d www.gaphoosh.ir
```

بعد از فعال شدن SSL، Cloudflare را روی Full (strict) بگذار.

## 16) آپدیت نسخه‌های بعدی

```bash
cd /var/www/gaphoosh
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php setup.php
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx
```

## چک‌لیست قبل از عمومی کردن

- `.env` داخل Git نیست.
- `APP_DEBUG=false` است.
- رمز ادمین قوی است.
- `FIRST_REGISTERED_USER_IS_ADMIN=false` است.
- diagnose Dahl سبز است.
- Cloudflare cache برای `/api/*` خاموش است.
- Nginx فقط `public` را serve می‌کند.
- پنل `/admin` فقط برای ادمین باز است.

## v1.1.0 additions: OTP, rate limits, logs and backups

Add these variables to production `.env`:

```env
MELIPAYAMAK_API_KEY="YOUR_REAL_MELIPAYAMAK_API_KEY"
MELIPAYAMAK_SSL_VERIFY=true
OTP_TTL_SECONDS=120
OTP_MAX_ATTEMPTS=5
OTP_DEV_MODE=false

RATE_CHAT_USER_PER_MINUTE=8
RATE_CHAT_IP_PER_MINUTE=30
RATE_LOGIN_IP_PER_10MIN=20
RATE_LOGIN_MOBILE_PER_10MIN=8
RATE_OTP_IP_PER_HOUR=12
RATE_OTP_MOBILE_PER_10MIN=3

MAX_INPUT_CHARS=12000
MAX_PROMPT_CHARS_HARD=20000
LONG_PROMPT_CHARS=7000
LONG_PROMPT_DAILY_LIMIT=12
BACKUP_RETENTION_DAYS=7
```

After pulling the new version, run:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php setup.php
sudo chown -R www-data:www-data storage
```

### Daily MySQL backup cron

```bash
sudo crontab -e
```

Add:

```cron
30 3 * * * cd /var/www/gaphoosh && /usr/bin/php tools/mysql-backup.php >> storage/logs/backup.log 2>&1
```

Then test manually:

```bash
cd /var/www/gaphoosh
sudo -u www-data php tools/mysql-backup.php
ls -lh storage/backups
```

If backup fails, check:

```bash
tail -100 storage/logs/backup.log
```

### Cloudflare notes

Do not cache these paths:

```text
/api/*
/chat*
/admin*
/login*
/register*
/terms
/privacy
```

Cache `/assets/*` aggressively.
