<?php

declare(strict_types=1);

use App\AdminRepository;
use App\Auth;
use App\ChatRepository;
use App\DahlClient;
use App\Database;
use App\MeliPayamakClient;
use App\OtpService;
use App\RateLimiter;
use App\SecurityLogger;

require dirname(__DIR__) . '/app/bootstrap.php';

$nonce = bin2hex(random_bytes(16));
security_headers($nonce);

$db = new Database();
$auth = new Auth($db);
$chats = new ChatRepository($db);
$adminRepo = new AdminRepository($db);
$dahl = new DahlClient();
$rateLimiter = new RateLimiter($db);
$securityLogger = new SecurityLogger($db);
$otpService = new OtpService($db, new MeliPayamakClient(), $securityLogger);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($uri, '/');
if ($path === '//') {
    $path = '/';
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET' && $path === '/') {
        view('landing', [
            'title' => 'گپ‌هوش | چت با هوش مصنوعی فارسی رایگان',
            'description' => 'گپ‌هوش یک پلتفرم فارسی و راست‌چین برای چت با مدل‌های مختلف هوش مصنوعی است؛ سریع، ساده، امن و مناسب تولید محتوا، یادگیری و کدنویسی.',
            'bodyClass' => 'landing-body',
            'nonce' => $nonce,
        ]);
    }



    if ($method === 'GET' && $path === '/terms') {
        view('terms', [
            'title' => 'قوانین استفاده | گپ‌هوش',
            'description' => 'قوانین استفاده از سرویس هوش مصنوعی فارسی گپ‌هوش، محدودیت‌ها، مسئولیت کاربر و شرایط استفاده.',
            'bodyClass' => 'landing-body legal-body',
            'nonce' => $nonce,
        ]);
    }

    if ($method === 'GET' && $path === '/privacy') {
        view('privacy', [
            'title' => 'حریم خصوصی | گپ‌هوش',
            'description' => 'سیاست حریم خصوصی گپ‌هوش درباره ذخیره شماره موبایل، گفتگوها، لاگ‌های امنیتی و مصرف API.',
            'bodyClass' => 'landing-body legal-body',
            'nonce' => $nonce,
        ]);
    }

    if ($method === 'GET' && $path === '/contact') {
        view('contact', [
            'title' => 'ارتباط با ما | گپ‌هوش',
            'description' => 'راه‌های ارتباط با گپ‌هوش؛ ایمیل، تلگرام و گیت‌هاب برای پشتیبانی، همکاری و گزارش مشکل.',
            'bodyClass' => 'landing-body contact-body',
            'nonce' => $nonce,
        ]);
    }

    if ($method === 'GET' && $path === '/login') {
        if (current_user()) {
            redirect_to(public_url('chat'));
        }
        view('login', [
            'title' => 'ورود به گپ‌هوش',
            'description' => 'ورود به پنل گفت‌وگو با هوش مصنوعی فارسی گپ‌هوش.',
            'bodyClass' => 'auth-body',
            'nonce' => $nonce,
        ]);
    }

    if ($method === 'POST' && $path === '/login') {
        verify_csrf();
        $mobileForLog = normalize_mobile((string) ($_POST['mobile'] ?? ''));
        try {
            $rateLimiter->hit('login:ip:' . client_ip(), (int) config('rate_login_ip_per_10min'), 600, 900);
            if ($mobileForLog !== '') {
                $rateLimiter->hit('login:mobile:' . $mobileForLog, (int) config('rate_login_mobile_per_10min'), 600, 900);
            }
            $loggedUser = $auth->login((string) ($_POST['mobile'] ?? ''), (string) ($_POST['password'] ?? ''));
            $securityLogger->log('login_success', (int) $loggedUser['id'], $mobileForLog);
            redirect_to(public_url('chat'));
        } catch (Throwable $e) {
            $securityLogger->log('login_failed', null, $mobileForLog, ['error' => $e->getMessage()]);
            view('login', [
                'title' => 'ورود به گپ‌هوش',
                'description' => 'ورود به پنل گفت‌وگو با هوش مصنوعی فارسی گپ‌هوش.',
                'bodyClass' => 'auth-body',
                'error' => $e->getMessage(),
                'old' => ['mobile' => (string) ($_POST['mobile'] ?? '')],
                'nonce' => $nonce,
            ]);
        }
    }

    if ($method === 'GET' && $path === '/register') {
        if (current_user()) {
            redirect_to(public_url('chat'));
        }
        view('register', [
            'title' => 'ثبت‌نام در گپ‌هوش',
            'description' => 'ثبت‌نام در گپ‌هوش و شروع چت با هوش مصنوعی فارسی.',
            'bodyClass' => 'auth-body',
            'nonce' => $nonce,
        ]);
    }

    if ($method === 'POST' && $path === '/register') {
        verify_csrf();
        $mobileForLog = normalize_mobile((string) ($_POST['mobile'] ?? ''));
        try {
            $rateLimiter->hit('register:ip:' . client_ip(), 12, 3600, 1800);
            $otpService->verify($mobileForLog, (string) ($_POST['otp_code'] ?? ''), 'register');
            $registeredUser = $auth->register(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['mobile'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? '')
            );
            $securityLogger->log('register_success', (int) $registeredUser['id'], $mobileForLog);
            redirect_to(public_url('chat'));
        } catch (Throwable $e) {
            $securityLogger->log('register_failed', null, $mobileForLog, ['error' => $e->getMessage()]);
            view('register', [
                'title' => 'ثبت‌نام در گپ‌هوش',
                'description' => 'ثبت‌نام در گپ‌هوش و شروع چت با هوش مصنوعی فارسی.',
                'bodyClass' => 'auth-body',
                'error' => $e->getMessage(),
                'old' => [
                    'name' => (string) ($_POST['name'] ?? ''),
                    'mobile' => (string) ($_POST['mobile'] ?? ''),
                    'email' => (string) ($_POST['email'] ?? ''),
                ],
                'nonce' => $nonce,
            ]);
        }
    }

    if ($method === 'POST' && $path === '/api/auth/send-otp') {
        verify_csrf();
        $data = json_input();
        $mobile = normalize_mobile((string) ($data['mobile'] ?? ''));
        try {
            $rateLimiter->hit('otp:ip:' . client_ip(), (int) config('rate_otp_ip_per_hour'), 3600, 1800);
            $rateLimiter->hit('otp:mobile:' . $mobile, (int) config('rate_otp_mobile_per_10min'), 600, 900);
            if ($db->fetch('SELECT id FROM users WHERE mobile = :mobile LIMIT 1', ['mobile' => $mobile])) {
                json_response(['error' => 'این شماره قبلاً ثبت شده است. وارد حساب کاربری شوید.'], 409);
            }
            $otpService->send($mobile, 'register');
            json_response([
                'ok' => true,
                'message' => 'کد تأیید ارسال شد. اعتبار کد حدود ' . (int) ((int) config('otp_ttl_seconds') / 60) . ' دقیقه است.',
                'dev_code' => (bool) config('otp_dev_mode', false) ? ($_SESSION['_dev_otp_code'] ?? null) : null,
            ]);
        } catch (Throwable $e) {
            $securityLogger->log('otp_send_failed', null, $mobile, ['error' => $e->getMessage()]);
            json_response(['error' => $e->getMessage()], $e->getCode() === 429 ? 429 : 422);
        }
    }

    if ($method === 'POST' && $path === '/logout') {
        verify_csrf();
        $auth->logout();
        redirect_to(public_url(''));
    }

    if ($method === 'GET' && $path === '/chat') {
        $user = require_auth();
        view('chat', [
            'title' => 'گفتگو با هوش مصنوعی | گپ‌هوش',
            'description' => 'پنل چت فارسی و راست‌چین گپ‌هوش برای گفت‌وگو با مدل‌های مختلف هوش مصنوعی.',
            'bodyClass' => 'chat-body',
            'user' => $user,
            'nonce' => $nonce,
        ]);
    }

    if ($method === 'GET' && $path === '/admin') {
        $user = require_admin();
        view('admin', [
            'title' => 'پنل مدیریت گپ‌هوش',
            'description' => 'مدیریت کاربران، گفتگوها و مصرف API در گپ‌هوش.',
            'bodyClass' => 'admin-body',
            'user' => $user,
            'nonce' => $nonce,
        ]);
    }

    if (str_starts_with($path, '/api/admin/')) {
        $user = require_admin();
        if ($method !== 'GET') {
            verify_csrf();
        }

        if ($method === 'GET' && $path === '/api/admin/dashboard') {
            json_response($adminRepo->dashboard());
        }

        if ($method === 'POST' && $path === '/api/admin/users/status') {
            $data = json_input();
            $adminRepo->setUserStatus((int) $user['id'], (int) ($data['user_id'] ?? 0), (string) ($data['status'] ?? ''));
            json_response(['ok' => true]);
        }

        if ($method === 'POST' && $path === '/api/admin/users/admin') {
            $data = json_input();
            $adminRepo->setUserAdmin((int) $user['id'], (int) ($data['user_id'] ?? 0), (bool) ($data['is_admin'] ?? false));
            json_response(['ok' => true]);
        }

        if ($method === 'POST' && $path === '/api/admin/conversations/delete') {
            $data = json_input();
            $adminRepo->deleteConversation((int) ($data['conversation_id'] ?? 0));
            json_response(['ok' => true]);
        }

        if ($method === 'GET' && $path === '/api/admin/dahl-diagnostics') {
            $model = trim((string) ($_GET['model'] ?? config('default_model')));
            json_response($dahl->diagnostics($model));
        }
    }

    if (str_starts_with($path, '/api/')) {
        $user = require_auth();

        if ($method !== 'GET') {
            verify_csrf();
        }

        if ($method === 'GET' && $path === '/api/bootstrap') {
            $models = $dahl->modelsWithFallback();
            $statusMap = [];
            try {
                $status = $dahl->status('24h');
                foreach (($status['models'] ?? []) as $item) {
                    if (is_array($item) && !empty($item['id'])) {
                        $statusMap[(string) $item['id']] = $item;
                    }
                }
            } catch (Throwable) {
                $statusMap = [];
            }

            foreach ($models as &$model) {
                $info = $statusMap[$model['id'] ?? ''] ?? [];
                $model['operational'] = $info['operational'] ?? null;
                $model['uptime'] = $info['uptime'] ?? null;
            }
            unset($model);

            json_response([
                'models' => $models,
                'default_model' => config('default_model'),
                'conversations' => $chats->listConversations((int) $user['id']),
                'balance' => $dahl->balance(),
                'used_today' => $chats->userMessagesToday((int) $user['id']),
                'max_daily_messages' => config('max_daily_messages'),
                'max_input_chars' => config('max_input_chars'),
                'long_prompt_chars' => config('long_prompt_chars'),
                'user' => $user,
            ]);
        }

        if ($method === 'GET' && $path === '/api/conversations') {
            json_response(['conversations' => $chats->listConversations((int) $user['id'])]);
        }

        if ($method === 'GET' && $path === '/api/messages') {
            $conversationId = (int) ($_GET['conversation_id'] ?? 0);
            if ($conversationId <= 0) {
                json_response(['error' => 'شناسه گفتگو معتبر نیست.'], 422);
            }
            json_response(['messages' => $chats->messages($conversationId, (int) $user['id'])]);
        }

        if ($method === 'POST' && $path === '/api/conversations') {
            $data = json_input();
            $model = trim((string) ($data['model'] ?? config('default_model')));
            $conversationId = $chats->createConversation((int) $user['id'], $model);
            json_response(['conversation_id' => $conversationId]);
        }

        if ($method === 'POST' && $path === '/api/conversations/rename') {
            $data = json_input();
            $chats->renameConversation((int) ($data['conversation_id'] ?? 0), (int) $user['id'], (string) ($data['title'] ?? ''));
            json_response(['ok' => true]);
        }

        if ($method === 'POST' && $path === '/api/conversations/delete') {
            $data = json_input();
            $chats->deleteConversation((int) ($data['conversation_id'] ?? 0), (int) $user['id']);
            json_response(['ok' => true]);
        }

        if ($method === 'POST' && $path === '/api/chat/stream') {
            $data = json_input();
            $content = trim((string) ($data['message'] ?? ''));
            $model = trim((string) ($data['model'] ?? config('default_model')));
            $conversationId = (int) ($data['conversation_id'] ?? 0);

            if ($content === '') {
                json_response(['error' => 'پیام خالی است.'], 422);
            }
            $contentLength = mb_strlen($content, 'UTF-8');
            if ($contentLength > (int) config('max_prompt_chars_hard')) {
                $securityLogger->log('prompt_hard_limit', (int) $user['id'], (string) $user['mobile'], ['length' => $contentLength]);
                json_response(['error' => 'پیام بیش از حد مجاز طولانی است. آن را کوتاه‌تر کن.'], 422);
            }
            if ($contentLength > (int) config('max_input_chars')) {
                json_response(['error' => 'پیام خیلی طولانی است.'], 422);
            }
            if ($contentLength >= (int) config('long_prompt_chars') && $chats->longUserPromptsToday((int) $user['id'], (int) config('long_prompt_chars')) >= (int) config('long_prompt_daily_limit')) {
                $securityLogger->log('long_prompt_abuse', (int) $user['id'], (string) $user['mobile'], ['length' => $contentLength]);
                json_response(['error' => 'امروز تعداد پیام‌های خیلی طولانی شما بیش از حد مجاز شده است.'], 429);
            }
            try {
                $rateLimiter->hit('chat:user:' . (int) $user['id'], (int) config('rate_chat_user_per_minute'), 60, 120);
                $rateLimiter->hit('chat:ip:' . client_ip(), (int) config('rate_chat_ip_per_minute'), 60, 120);
            } catch (Throwable $e) {
                $securityLogger->log('rate_limited_chat', (int) $user['id'], (string) $user['mobile'], ['error' => $e->getMessage()]);
                json_response(['error' => $e->getMessage()], 429);
            }

            $usedToday = $chats->userMessagesToday((int) $user['id']);
            if ($usedToday >= (int) config('max_daily_messages')) {
                json_response(['error' => 'سقف پیام روزانه شما تمام شده است.'], 429);
            }

            if ($conversationId > 0 && !$chats->findOwnedConversation($conversationId, (int) $user['id'])) {
                json_response(['error' => 'گفتگو پیدا نشد.'], 404);
            }

            $firstMessageCount = 0;
            if ($conversationId <= 0) {
                $conversationId = $chats->createConversation((int) $user['id'], $model, truncate_fa($content, 55));
            } else {
                $firstMessageCount = $chats->firstUserMessageCount($conversationId);
            }

            $title = $firstMessageCount === 0 ? truncate_fa($content, 55) : null;
            $chats->addMessage($conversationId, (int) $user['id'], 'user', $content, $model);
            $chats->touchConversation($conversationId, $model, $title);
            $history = $chats->conversationForModel($conversationId, (int) config('history_limit'));
            array_unshift($history, ['role' => 'system', 'content' => (string) config('system_prompt')]);

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            session_write_close();
            set_time_limit((int) config('stream_timeout', 180) + 20);

            send_sse('meta', [
                'conversation_id' => $conversationId,
                'title' => $title,
                'model' => $model,
                'updated_at' => date('Y-m-d H:i:s'),
                'used_today' => $usedToday + 1,
                'max_daily_messages' => config('max_daily_messages'),
            ]);

            $usage = [];
            try {
                $result = $dahl->streamChatCompletion(
                    $model,
                    $history,
                    static function (string $delta): void {
                        send_sse('delta', ['text' => $delta]);
                    },
                    static function (array $newUsage) use (&$usage): void {
                        $usage = $newUsage;
                        send_sse('usage', $usage);
                    }
                );

                $usage = $result['usage'] ?: $usage;
                $chats->addMessage($conversationId, (int) $user['id'], 'assistant', (string) $result['content'], $model, $usage, [
                    'fallback_mode' => $result['fallback_mode'] ?? null,
                ]);
                $chats->touchConversation($conversationId, $model, $title);
                $chats->logApi((int) $user['id'], $conversationId, $model, true, (int) ($result['status_code'] ?? 200), null, $usage);
                send_sse('done', [
                    'conversation_id' => $conversationId,
                    'title' => $title,
                    'usage' => $usage,
                    'fallback_mode' => $result['fallback_mode'] ?? null,
                    'used_today' => $chats->userMessagesToday((int) $user['id']),
                    'max_daily_messages' => config('max_daily_messages'),
                ]);
                exit;
            } catch (Throwable $e) {
                $code = $e->getCode() > 0 ? (int) $e->getCode() : 500;
                $chats->logApi((int) $user['id'], $conversationId, $model, false, $code, $e->getMessage());
                send_sse('error', ['message' => 'خطا در ارتباط با مدل: ' . $e->getMessage(), 'conversation_id' => $conversationId]);
                exit;
            }
        }
    }

    http_response_code(404);
    echo 'صفحه پیدا نشد.';
} catch (Throwable $e) {
    app_log('unhandled-exception', ['path' => $path, 'method' => $method, 'error' => $e->getMessage()]);
    if (str_starts_with($path, '/api/')) {
        json_response(['error' => (bool) config('app_debug') ? $e->getMessage() : 'خطای داخلی سرور.'], 500);
    }
    http_response_code(500);
    echo (bool) config('app_debug') ? h($e->getMessage()) : 'خطای داخلی سرور.';
}
