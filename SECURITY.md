# نکات امنیتی گپ‌هوش

## کلید Dahl

کلید Dahl فقط باید در `.env` باشد:

```env
DAHL_API_KEY="..."
```

این کلید نباید در این مکان‌ها قرار بگیرد:

- فایل‌های public
- JavaScript
- HTML
- Git repository
- دیتابیس کاربران
- README عمومی

## محافظت‌های پیاده‌سازی‌شده

- CSRF token برای فرم‌ها و APIهای POST
- Prepared statements در queryها
- `password_hash` و `password_verify` برای رمز عبور
- Session cookie با HttpOnly و SameSite
- Content Security Policy بدون CDN
- عدم نمایش کلید API در مرورگر
- محدودیت پیام روزانه
- soft delete برای گفتگوها
- پنل ادمین با سطح دسترسی `is_admin`
- امکان مسدودسازی کاربران
- لاگ درخواست‌های Dahl و خطاها

## چیزهایی که برای نسخه عمومی هنوز باید اضافه شود

این نسخه برای MVP و تست محلی خوب است، ولی برای انتشار عمومی جدی هنوز این‌ها لازم‌اند:

1. OTP واقعی برای تأیید شماره موبایل
2. کپچا یا rate limit جدی برای ثبت‌نام و ورود
3. محدودیت IP روی endpointهای حساس
4. queue/logging حرفه‌ای برای درخواست‌های طولانی
5. backup منظم MySQL
6. HTTPS اجباری
7. rotation کلید Dahl در صورت افشا شدن
8. مانیتورینگ مصرف توکن و خطاهای ۴۰۳/۵۰۲

## تنظیم production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL="https://gaphoosh.ir"
```

در production هرگز خطای خام PHP را به کاربر نشان نده.

## Production security controls

- OTP codes are stored as password hashes, not plaintext.
- Registration requires verified mobile number.
- Failed login, OTP failures, rate limit events and suspicious long prompts are logged in `security_logs`.
- Chat endpoints are rate-limited by user and IP.
- OTP endpoints are rate-limited by mobile and IP.
- Very long prompts are blocked or limited to reduce API-cost abuse.
- MySQL backups are stored under `storage/backups/` and must never be served publicly or committed to Git.

## MeliPayamak API key

Store the MeliPayamak API key only in `.env`:

```env
MELIPAYAMAK_API_KEY="..."
```

Never commit it to GitHub. Never put it in frontend JavaScript.
