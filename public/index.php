<?php

declare(strict_types=1);

use App\AdminRepository;
use App\Auth;
use App\ChatRepository;
use App\DahlClient;
use App\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

$nonce = bin2hex(random_bytes(16));
security_headers($nonce);

$db = new Database();
$auth = new Auth($db);
$chats = new ChatRepository($db);
$adminRepo = new AdminRepository($db);
$dahl = new DahlClient();

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
        try {
            $auth->login((string) ($_POST['mobile'] ?? ''), (string) ($_POST['password'] ?? ''));
            redirect_to(public_url('chat'));
        } catch (Throwable $e) {
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
        try {
            $auth->register(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['mobile'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? '')
            );
            redirect_to(public_url('chat'));
        } catch (Throwable $e) {
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
            if (mb_strlen($content, 'UTF-8') > (int) config('max_input_chars')) {
                json_response(['error' => 'پیام خیلی طولانی است.'], 422);
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
    if (str_starts_with($path, '/api/')) {
        json_response(['error' => (bool) config('app_debug') ? $e->getMessage() : 'خطای داخلی سرور.'], 500);
    }
    http_response_code(500);
    echo (bool) config('app_debug') ? h($e->getMessage()) : 'خطای داخلی سرور.';
}
