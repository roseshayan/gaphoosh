# انتشار گپ‌هوش روی GitHub

## قبل از اولین commit

این فایل‌ها نباید وارد Git شوند:

- `.env`
- `vendor/`
- `node_modules/`
- `storage/cache/*.json`
- `storage/logs/*.log`
- هر فایل credential یا token

اگر قبلاً credential Dahl را جایی گذاشتی، همان token را rotate کن. فقط `.env.example` باید در Git باشد.

## ساخت ریپو

```bash
cd C:\laragon\www\gaphoosh
git init
git add .
git commit -m "Release GapHoosh v1.0.0"
git branch -M main
git remote add origin https://github.com/roseshayan/gaphoosh.git
git push -u origin main
```

اگر با GitHub CLI کار می‌کنی:

```bash
gh repo create roseshayan/gaphoosh --private --source=. --remote=origin --push
```

پیشنهاد من برای شروع: ریپو را private بگذار. وقتی امنیت، rate limit، قوانین حریم خصوصی و هزینه مصرف AI را جدی کردی، public کن.

## تگ نسخه 1

```bash
git tag -a v1.0.0 -m "GapHoosh v1.0.0"
git push origin v1.0.0
```

بعد در GitHub از بخش Releases یک release بساز:

- Tag: `v1.0.0`
- Title: `GapHoosh v1.0.0`
- Notes:
  - Persian RTL AI chat
  - MySQL chat history
  - SSE streaming
  - Admin panel
  - Dahl provider diagnostics
  - Contact page

## فایل‌هایی که برای محصول واقعی بهتر است داشته باشی

- `README.md`
- `SECURITY.md`
- `.env.example`
- `docs/DEPLOY_UBUNTU_24.md`
- `docs/GITHUB_RELEASE.md`
- `CHANGELOG.md`

## متن کوتاه معرفی ریپو

```text
گپ‌هوش یک وب‌اپ فارسی و راست‌چین برای گفتگو با مدل‌های هوش مصنوعی است؛ با PHP، MySQL، استریم SSE، مدیریت گفتگو، پنل ادمین و اتصال به Dahl API.
```
