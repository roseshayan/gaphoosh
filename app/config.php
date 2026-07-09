<?php

declare(strict_types=1);

return [
    'app_name' => env_value('APP_NAME', 'گپ‌هوش'),
    'app_url' => rtrim((string) env_value('APP_URL', 'http://gaphoosh.test'), '/'),
    'app_env' => env_value('APP_ENV', 'production'),
    'app_debug' => (bool) env_value('APP_DEBUG', false),
    'timezone' => env_value('APP_TIMEZONE', 'Asia/Tehran'),

    'db_host' => env_value('DB_HOST', '127.0.0.1'),
    'db_port' => env_value('DB_PORT', '3306'),
    'db_database' => env_value('DB_DATABASE', 'gaphoosh'),
    'db_username' => env_value('DB_USERNAME', 'root'),
    'db_password' => env_value('DB_PASSWORD', ''),

    'dahl_base_url' => rtrim((string) env_value('DAHL_BASE_URL', 'https://inference.dahl.global/v1'), '/'),
    'dahl_token_url' => rtrim((string) env_value('DAHL_TOKEN_URL', 'https://inference.dahl.global/tokens'), '/'),
    'dahl_api_key' => env_value('DAHL_API_KEY', ''),
    'dahl_auth_mode' => env_value('DAHL_AUTH_MODE', 'auto'),
    'dahl_auto_token_on_auth_error' => (bool) env_value('DAHL_AUTO_TOKEN_ON_AUTH_ERROR', true),
    'dahl_auto_token_ttl_seconds' => (int) env_value('DAHL_AUTO_TOKEN_TTL_SECONDS', 604800),
    'default_model' => env_value('DAHL_DEFAULT_MODEL', 'MiniMaxAI/MiniMax-M2.7'),

    'max_daily_messages' => (int) env_value('MAX_DAILY_MESSAGES', 80),
    'max_input_chars' => (int) env_value('MAX_INPUT_CHARS', 12000),
    'history_limit' => (int) env_value('HISTORY_LIMIT', 24),
    'stream_timeout' => (int) env_value('STREAM_TIMEOUT_SECONDS', 180),
    'dahl_stream_fallback' => (bool) env_value('DAHL_STREAM_FALLBACK', true),

    'rate_chat_user_per_minute' => (int) env_value('RATE_CHAT_USER_PER_MINUTE', 8),
    'rate_chat_ip_per_minute' => (int) env_value('RATE_CHAT_IP_PER_MINUTE', 30),
    'rate_login_ip_per_10min' => (int) env_value('RATE_LOGIN_IP_PER_10MIN', 20),
    'rate_login_mobile_per_10min' => (int) env_value('RATE_LOGIN_MOBILE_PER_10MIN', 8),
    'rate_otp_ip_per_hour' => (int) env_value('RATE_OTP_IP_PER_HOUR', 12),
    'rate_otp_mobile_per_10min' => (int) env_value('RATE_OTP_MOBILE_PER_10MIN', 3),
    'max_prompt_chars_hard' => (int) env_value('MAX_PROMPT_CHARS_HARD', 20000),
    'long_prompt_chars' => (int) env_value('LONG_PROMPT_CHARS', 7000),
    'long_prompt_daily_limit' => (int) env_value('LONG_PROMPT_DAILY_LIMIT', 12),

    'melipayamak_api_key' => env_value('MELIPAYAMAK_API_KEY', ''),
    'melipayamak_ssl_verify' => (bool) env_value('MELIPAYAMAK_SSL_VERIFY', true),
    'otp_ttl_seconds' => (int) env_value('OTP_TTL_SECONDS', 120),
    'otp_max_attempts' => (int) env_value('OTP_MAX_ATTEMPTS', 5),
    'otp_dev_mode' => (bool) env_value('OTP_DEV_MODE', false),

    'admin_mobile' => env_value('ADMIN_MOBILE', ''),
    'admin_password' => env_value('ADMIN_PASSWORD', ''),
    'first_registered_user_is_admin' => (bool) env_value('FIRST_REGISTERED_USER_IS_ADMIN', true),

    'contact_email' => env_value('CONTACT_EMAIL', 'namayandeshayan@gmail.com'),
    'contact_telegram' => env_value('CONTACT_TELEGRAM', 'https://t.me/SudoShayanNA'),
    'contact_github' => env_value('CONTACT_GITHUB', 'https://github.com/roseshayan'),

    'system_prompt' => 'تو دستیار فارسی گپ‌هوش هستی. پاسخ‌ها را دقیق، روشن، فارسی و بدون حاشیه بده. خروجی را با Markdown تمیز بنویس: تیترهای کوتاه، لیست‌های خوانا و جدول فقط وقتی واقعاً لازم است. اگر جواب شامل کد است، فقط کد را داخل بلاک Markdown سه‌تایی با نام زبان بنویس؛ واژه‌هایی مثل «کپی» را داخل متن یا کد ننویس. توضیح فارسی را جدا، راست‌چین‌پذیر و خوانا بنویس. اگر مطمئن نیستی، شفاف بگو.',
];
