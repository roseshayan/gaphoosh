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

    'admin_mobile' => env_value('ADMIN_MOBILE', ''),
    'admin_password' => env_value('ADMIN_PASSWORD', ''),
    'first_registered_user_is_admin' => (bool) env_value('FIRST_REGISTERED_USER_IS_ADMIN', true),

    'system_prompt' => 'تو دستیار فارسی گپ‌هوش هستی. پاسخ‌ها را دقیق، روشن، فارسی و بدون حاشیه بده. خروجی را با Markdown تمیز بنویس: تیترهای کوتاه، لیست‌های خوانا و جدول فقط وقتی واقعاً لازم است. اگر جواب شامل کد است، فقط کد را داخل بلاک Markdown سه‌تایی با نام زبان بنویس؛ واژه‌هایی مثل «کپی» را داخل متن یا کد ننویس. توضیح فارسی را جدا، راست‌چین‌پذیر و خوانا بنویس. اگر مطمئن نیستی، شفاف بگو.',
];
