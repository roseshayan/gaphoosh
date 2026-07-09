# گپ‌هوش - GapHoosh

وب‌سایت فارسی و راست‌چین برای چت با هوش مصنوعی Dahl، با ثبت‌نام/ورود، دیتابیس MySQL، مدیریت گفتگوها، استریم شبیه ChatGPT، نمایش تمیز کد و پنل مدیریت.

## تکنولوژی‌ها

- PHP 8.1+
- MySQL 8 یا MariaDB 10.6+
- Composer
- NPM برای assetهای محلی و فونت Vazir/Vazirmatn
- بدون CDN
- API سازگار با OpenAI از Dahl

## امکانات نسخه 4.0

- لندینگ پیج جدید، ریسپانسیو، فارسی، سبک و سئو شده
- لوگوی گپ‌هوش داخل پروژه: `public/assets/img/logo.png`
- فونت Vazir/Vazirmatn از NPM و به‌صورت local
- ثبت‌نام با شماره موبایل اجباری
- ایمیل اختیاری
- ورود با شماره موبایل و رمز عبور
- پنل چت فارسی و راست‌چین
- انتخاب همه مدل‌های Dahl از endpoint زنده `/v1/models`
- fallback داخلی برای مدل‌ها: MiniMax، Kimi و GLM FP8
- استریم پاسخ‌ها با SSE
- اگر Dahl برای stream یا توکن خطای 401/402/403 بدهد، پروژه یک بار توکن inference تازه از `POST /tokens` می‌گیرد و دوباره تست می‌کند
- حالت `DAHL_AUTH_MODE=auto` برای Laragon و تست محلی اضافه شد
- ابزار عیب‌یابی `php tools/dahl-diagnose.php` اضافه شد
- عیب‌یابی اتصال Dahl از داخل پنل مدیریت `/admin` اضافه شد
- اضافه شدن گفتگوی جدید به لیست گفتگوها همان لحظه شروع ارسال پیام
- ذخیره کامل چت‌ها و پیام‌ها در MySQL
- ادامه دادن گفتگوهای قبلی
- نمایش کدها در محیط چپ‌چین، تاریک و قابل کپی
- تشخیص جهت متن: فارسی راست‌چین، کد چپ‌چین
- پنل مدیریت در `/admin`
- مدیریت کاربران: فعال/مسدود، ادمین/عادی
- مشاهده گفتگوهای اخیر و حذف نرم گفتگو
- مشاهده لاگ API و خطاهای Dahl
- محدودیت پیام روزانه برای کنترل مصرف توکن
- CSRF protection
- Session امن با HttpOnly و SameSite
- Prepared statements برای queryها
- Content Security Policy بدون CDN

## نصب روی Laragon

### 1. پروژه را کپی کن

```bash
C:\laragon\www\gaphoosh-ai
```

### 2. پکیج‌ها را نصب کن

```bash
cd C:\laragon\www\gaphoosh-ai
composer install
npm install
npm run build
```

`npm run build` فونت Vazir/Vazirmatn را از `node_modules` به `public/assets/fonts` کپی می‌کند.

### 3. فایل env را بساز

```bash
copy .env.example .env
```

داخل `.env`:

```env
APP_NAME="گپ‌هوش"
APP_URL="http://gaphoosh.test"
APP_ENV=local
APP_DEBUG=true

DB_DATABASE=gaphoosh
DB_USERNAME=root
DB_PASSWORD=

# پیشنهاد برای تست Laragon:
DAHL_AUTH_MODE=auto
DAHL_API_KEY=""
DAHL_TOKEN_URL="https://inference.dahl.global/tokens"
DAHL_AUTO_TOKEN_ON_AUTH_ERROR=true
DAHL_DEFAULT_MODEL="MiniMaxAI/MiniMax-M2.7"
DAHL_STREAM_FALLBACK=true

# اگر خودت Bearer token واقعی گرفتی، این حالت را بگذار:
# DAHL_AUTH_MODE=static
# DAHL_API_KEY="توکن واقعی که از POST /tokens یا landing page گرفتی"

ADMIN_MOBILE="09123456789"
ADMIN_PASSWORD="یک_رمز_حداقل_۸_کاراکتری"
FIRST_REGISTERED_USER_IS_ADMIN=true
```

نکته مهم: مقدار `fingerprint` یا `api_key` حساب را کورکورانه داخل `DAHL_API_KEY` نگذار. طبق مستندات Dahl، درخواست inference باید با Bearer token ساخته‌شده از `POST /tokens` انجام شود. برای تست روی Laragon، بهترین حالت `DAHL_AUTH_MODE=auto` است تا پروژه خودش توکن inference بگیرد و در `storage/cache/dahl-token.json` ذخیره کند. هیچ کلیدی را داخل Git، JavaScript، HTML یا فایل‌های public نگذار.

### 4. دیتابیس MySQL را بساز/آپدیت کن

```bash
php setup.php
```

اگر دیتابیس قبلاً ساخته شده باشد، `setup.php` ستون `is_admin` را هم اضافه می‌کند.

### 5. ادمین کردن یک کاربر موجود

اگر کاربر قبلاً ثبت‌نام کرده و می‌خواهی ادمین شود:

```bash
php make-admin.php 09123456789
```

یا `ADMIN_MOBILE` و `ADMIN_PASSWORD` را در `.env` بگذار و دوباره اجرا کن:

```bash
php setup.php
```

### 6. تنظیم Laragon

Virtual Host:

```text
gaphoosh.test
```

Document Root:

```text
C:\laragon\www\gaphoosh-ai\public
```

بعد Apache/Nginx را restart کن و برو به:

```text
http://gaphoosh.test
```

## مسیرهای مهم

```text
/                         لندینگ پیج
/register                 ثبت‌نام
/login                    ورود
/chat                     پنل چت
/admin                    پنل مدیریت
/api/bootstrap            مدل‌ها، گفتگوها، سهمیه و موجودی
/api/chat/stream          ارسال پیام و دریافت پاسخ استریم
/api/admin/dashboard      داده‌های پنل مدیریت
/api/admin/dahl-diagnostics عیب‌یابی اتصال Dahl
```

## درباره خطای Dahl 403

خطای 403 در این پروژه معمولاً یعنی مقدار داخل `DAHL_API_KEY` برای endpoint inference پذیرفته نشده است. اشتباه رایج این است که credential حساب یا fingerprint/API key حساب را به‌جای Bearer token inference استفاده کنی.

راه پیشنهادی برای Laragon:

```env
DAHL_AUTH_MODE=auto
DAHL_API_KEY=""
DAHL_AUTO_TOKEN_ON_AUTH_ERROR=true
```

در این حالت پروژه خودش از endpoint ریشه `https://inference.dahl.global/tokens` توکن inference می‌گیرد و همان را روی `https://inference.dahl.global/v1/chat/completions` استفاده می‌کند.

برای تست دقیق:

```bash
php tools/dahl-diagnose.php
```

این دستور مدل‌ها، ساخت توکن، موجودی و یک chat completion کوتاه را تست می‌کند و توکن‌ها را ماسک‌شده نمایش می‌دهد.

اگر همچنان خطا دیدی:

1. از پنل مدیریت `/admin` بخش «عیب‌یابی اتصال Dahl» را اجرا کن.
2. مطمئن شو Laragon/PHP افزونه‌های `curl`, `openssl`, `pdo_mysql`, `mbstring` را دارد.
3. مدل `MiniMaxAI/MiniMax-M2.7` را اول تست کن؛ طبق مستندات Dahl مدل پیش‌فرض پیشنهادی است.
4. اگر فقط stream خطا بدهد، `DAHL_STREAM_FALLBACK=true` باعث می‌شود پروژه با non-stream پاسخ را بگیرد و در UI به شکل تدریجی نمایش دهد.

## نکته درباره استریم

استریم با `fetch` و `ReadableStream` در مرورگر انجام می‌شود و بک‌اند PHP خروجی را به‌صورت `text/event-stream` ارسال می‌کند. اگر روی Apache یا Nginx پاسخ‌ها یکجا آمدند، buffering سرور را بررسی کن. در کد header زیر تنظیم شده است:

```text
X-Accel-Buffering: no
```

## تنظیمات مصرف

```env
MAX_DAILY_MESSAGES=80
MAX_INPUT_CHARS=12000
HISTORY_LIMIT=24
STREAM_TIMEOUT_SECONDS=180
```

برای سایت عمومی، `MAX_DAILY_MESSAGES` را بی‌حساب بالا نبر. همه کاربران از همان کلید Dahl مصرف می‌کنند.

## بررسی syntax و assetها

```bash
composer check
npm run check
```

## آماده‌سازی برای انتشار روی gaphoosh.ir

```env
APP_ENV=production
APP_DEBUG=false
APP_URL="https://gaphoosh.ir"
```

HTTPS را فعال کن، permission فایل `.env` را محدود نگه دار و لاگ‌های API را مرتب بررسی کن.

## رفع خطای Dahl 403 در Laragon

اگر `php tools/dahl-diagnose.php` خطای 403 داد، اول `.env` را دقیقاً چک کنید:

```env
DAHL_BASE_URL="https://inference.dahl.global/v1"
DAHL_TOKEN_URL="https://inference.dahl.global/tokens"
DAHL_AUTH_MODE=auto
DAHL_API_KEY=""
DAHL_AUTO_TOKEN_ON_AUTH_ERROR=true
```

نکته مهم: endpointهای `GET /v1/models` و `GET /v1/status` عمومی هستند و نباید همراه Authorization صدا زده شوند. نسخه v5 این مورد را اصلاح کرده است. اگر همچنان `models_public` خطای 403 داد، یعنی فایل‌های نسخه جدید جایگزین نشده‌اند، URLها در `.env` اشتباه‌اند، یا ترافیک خروجی سیستم/شبکه شما به Dahl توسط فایروال/پروکسی محدود شده است.

برای تست خام بیرون از پروژه:

```bash
curl https://inference.dahl.global/v1/models
curl -X POST https://inference.dahl.global/tokens
```

اگر curl دوم 403 داد، مشکل از پروژه نیست و باید توکن را از landing page خود Dahl بگیرید و در حالت static قرار دهید.


## رفع خطای Cloudflare / 403 در Dahl

اگر در PowerShell یا PHP خروجی‌ای شبیه این دیدی:

```text
Enable JavaScript and cookies to continue
/cdn-cgi/challenge-platform/
__cf_chl_tk
```

این خطا یعنی درخواست به endpointهای Dahl توسط Cloudflare Managed Challenge متوقف شده است. این دیگر مشکل `DAHL_API_KEY` یا کد PHP نیست؛ چون endpoint عمومی مثل `/v1/models` هم باید بدون Authorization جواب JSON بدهد، اما به‌جای JSON صفحه HTML challenge برگشته است. PHP/cURL نمی‌تواند JavaScript Challenge و cookies مرورگر را پاس کند.

برای تست درست در PowerShell، حواست باشد `curl` در PowerShell معمولاً alias برای `Invoke-WebRequest` است. از یکی از این دو استفاده کن:

```powershell
curl.exe https://inference.dahl.global/v1/models
curl.exe -X POST https://inference.dahl.global/tokens
```

یا:

```powershell
Invoke-RestMethod -Uri "https://inference.dahl.global/v1/models" -Method GET
Invoke-RestMethod -Uri "https://inference.dahl.global/tokens" -Method POST
```

اگر همین دستورها هم Cloudflare Challenge دادند، راه‌حل واقعی یکی از این‌هاست:

1. پروژه را روی سرور/هاستی تست کن که IP آن توسط Dahl/Cloudflare challenge نمی‌شود.
2. از Dahl بخواه API endpoint را برای استفاده server-to-server بدون managed challenge فعال/whitelist کند.
3. از landing page خود Dahl در مرورگر توکن بگیر و `DAHL_AUTH_MODE=static` بگذار؛ البته اگر `/v1/chat/completions` از PHP همچنان challenge شود، این هم کافی نیست.
4. برای توسعه UI می‌توانی فعلاً روی fallback مدل‌ها کار کنی، اما چت واقعی بدون دسترسی server-to-server به Dahl انجام نمی‌شود.

نسخه v6 در `php tools/dahl-diagnose.php` بخش `http_probes` دارد. اگر `cloudflare_challenge: true` دیدی، دنبال تغییر مدل/کلید نرو؛ مشکل مسیر شبکه/Cloudflare است.

## نکته نسخه v7 درباره Dahl Diagnose

در نسخه v6، بخش `http_probes.tokens_post` یک درخواست واقعی `POST /tokens` می‌زد و بعد مرحله `token_creation` دوباره بلافاصله یک توکن جدید می‌ساخت. بعضی وضعیت‌های Dahl ممکن است درخواست دوم را با 400 رد کنند. در v7، اگر probe توکن موفق باشد، همان توکن در `storage/cache/dahl-token.json` ذخیره می‌شود و تست چت با همان توکن ادامه پیدا می‌کند.

همچنین برای `POST /tokens` بدنه خالی صریح ارسال می‌شود تا رفتار PHP/cURL به دستور مستندات `curl -X POST https://inference.dahl.global/tokens` نزدیک‌تر باشد.

برای تست:

```powershell
Remove-Item storage\cache\dahl-token.json -ErrorAction SilentlyContinue
php tools/dahl-diagnose.php
```

اگر `models_public`, `token_creation` و `chat_test` هر سه `ok: true` شدند، اتصال Dahl درست است.

## تغییرات نسخه v8

- رندر پیام‌های هوش مصنوعی بهتر شد: تیترها، لیست‌ها، جدول‌های Markdown، لینک‌ها، blockquote و code blockها خواناتر نمایش داده می‌شوند.
- برای هر پاسخ هوش مصنوعی دکمه «کپی پاسخ» اضافه شد تا متن خام کامل پاسخ کپی شود؛ دکمه «کپی کد» فقط کد همان بلاک را کپی می‌کند.
- هنگام دریافت پاسخ، دکمه ارسال به «توقف» تبدیل می‌شود. کاربر می‌تواند پاسخ را متوقف کند، گفتگوی جدید باز کند یا یک گفتگوی قدیمی را باز کند؛ UI دیگر بی‌خودی قفل نمی‌شود.
- prompt سیستمی اصلاح شد تا مدل‌ها کد را داخل fenced Markdown بنویسند و متن‌هایی مثل «کپی» را داخل پاسخ تولید نکنند.

### درباره Web Search

در مستندات فعلی Dahl که پروژه بر اساس آن پیاده‌سازی شده، endpoint مستقلی برای Web Search یا پارامتر رسمی web_search معرفی نشده است. اگر این قابلیت در UI خود Dahl وجود دارد، ممکن است قابلیت داخلی همان UI باشد و نه API عمومی. برای اضافه کردن Web Search در گپ‌هوش، بهتر است یک provider مستقل مثل Tavily، Brave Search API، Serper یا Bing اضافه شود و نتیجه جستجو به‌صورت context به مدل Dahl داده شود.
