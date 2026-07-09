# Changelog

## v1.0.0 - 2026-07-09

- لندینگ پیج فارسی و سئو شده
- ثبت‌نام با شماره موبایل اجباری و ایمیل اختیاری
- ورود کاربران
- چت با مدل‌های Dahl
- استریم پاسخ‌ها با SSE
- مدیریت گفتگوها و ذخیره پیام‌ها در MySQL
- نمایش خوانای Markdown و کدها
- کپی پاسخ و کپی بلاک کد
- پنل مدیریت کاربران، گفتگوها، لاگ API و عیب‌یابی Dahl
- صفحه ارتباط با ما
- فایل‌های راهنمای GitHub و Ubuntu 24.04

## v1.1.0 - Production hardening

- Added real mobile OTP registration through MeliPayamak API-key based endpoint.
- Added hashed OTP storage with expiry, attempt limit, and security logs.
- Added database-backed rate limiting for login, OTP and chat by IP/user/mobile.
- Added abuse controls for very long prompts.
- Added security logs for failed logins, OTP failures, rate limits and suspicious prompt usage.
- Added admin monitoring for security events, token usage and backup status.
- Added MySQL backup script with retention support.
- Added Terms of Use and Privacy Policy pages.
