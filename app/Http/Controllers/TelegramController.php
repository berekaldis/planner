<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TelegramController extends Controller
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Dedicated Telegram Bot Status & Management View
     */
    public function status(Request $request)
    {
        $isConfigured = $this->telegram->isConfigured();
        $botInfo = $isConfigured ? $this->telegram->getMe() : ['ok' => false];
        $webhookInfo = $isConfigured ? $this->telegram->getWebhookInfo() : ['ok' => false];

        if (!is_array($botInfo) || empty($botInfo['result']) || !is_array($botInfo['result'])) {
            $botInfo = [
                'ok' => false,
                'result' => [
                    'id' => null,
                    'first_name' => 'Kaldis Bot',
                    'username' => kaldis_setting('telegram_bot_username', 'KaldisPlannerBot'),
                ],
            ];
        }

        if (!is_array($webhookInfo) || empty($webhookInfo['result']) || !is_array($webhookInfo['result'])) {
            $webhookInfo = [
                'ok' => false,
                'result' => [
                    'url' => '',
                    'pending_update_count' => 0,
                    'has_custom_certificate' => false,
                ],
            ];
        }

        $users = User::with(['department', 'role'])->orderBy('role_id')->orderBy('username')->get();

        $recentMessages = DB::table('telegram_messages')
            ->orderByDesc('id')
            ->take(50)
            ->get();

        $totalMessagesCount = DB::table('telegram_messages')->count();
        $sentCount = DB::table('telegram_messages')->where('direction', 'OUTGOING')->count();
        $receivedCount = DB::table('telegram_messages')->where('direction', 'INCOMING')->count();

        $botToken = $this->telegram->getBotToken();
        $maskedToken = '';
        if (!empty($botToken)) {
            $parts = explode(':', $botToken, 2);
            $botId = $parts[0] ?? '';
            $secret = $parts[1] ?? '';
            $maskedToken = $botId . ':' . substr($secret, 0, 4) . '••••••••' . substr($secret, -4);
        }

        return view('telegram.status', compact(
            'isConfigured',
            'botInfo',
            'webhookInfo',
            'users',
            'recentMessages',
            'totalMessagesCount',
            'sentCount',
            'receivedCount',
            'maskedToken',
            'botToken'
        ));
    }

    /**
     * AJAX endpoint to test Telegram connection (getMe)
     */
    public function testConnection(Request $request)
    {
        $res = $this->telegram->getMe();
        return response()->json($res);
    }

    /**
     * Update, verify, and persist the Telegram Bot API Token
     */
    public function updateToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'regex:/^\d+:[A-Za-z0-9_-]{20,}$/'],
        ], [
            'token.regex' => 'Invalid Telegram bot token format. Tokens are formatted as 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ.',
        ]);

        $token = trim($validated['token']);

        // Verify with Telegram Bot API
        $service = new TelegramService($token);
        $me = $service->getMe();

        if (empty($me['ok'])) {
            $desc = $me['description'] ?? 'Unauthorized or connection error. Please verify the token with @BotFather.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'description' => "Telegram verification failed: {$desc}",
                ], 422);
            }
            return back()->with('error', "Telegram verification failed: {$desc}");
        }

        $botUsername = $me['result']['username'] ?? 'KaldisPlannerBot';
        $botName = $me['result']['first_name'] ?? 'Kaldis Bot';

        // 1. Persist to system_settings table
        SystemSetting::set('telegram_bot_token', $token, 'TELEGRAM', 'Telegram Bot API Token (from @BotFather)');
        SystemSetting::set('telegram_bot_username', $botUsername, 'TELEGRAM', 'Telegram Bot Username');

        // 2. Persist to .env if writable
        $this->updateEnvFile('TELEGRAM_BOT_TOKEN', $token);
        $this->updateEnvFile('TELEGRAM_BOT_USERNAME', $botUsername);

        // 3. Clear configuration cache
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        } catch (\Throwable $e) {}

        AuditLog::log('UPDATE_TELEGRAM_TOKEN', 'telegram', "Updated Telegram Bot Token to @{$botUsername} ({$botName})");

        $msg = "Telegram Bot Token updated successfully! Connected to @{$botUsername} ({$botName}).";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $msg,
                'bot' => $me['result'],
            ]);
        }

        return back()->with('success', $msg);
    }

    private function updateEnvFile(string $key, string $value): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath) || !is_writable($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);
        $escapedValue = '"' . addcslashes($value, '"\\$') . '"';

        if (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $content);
        } else {
            $content .= "\n{$key}={$escapedValue}\n";
        }

        file_put_contents($envPath, $content);
    }

    /**
     * AJAX / POST endpoint to send a test message
     */
    public function sendTestMessage(Request $request)
    {
        $validated = $request->validate([
            'chat_id' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'message' => 'required|string|max:1000',
        ]);

        $chatId = $validated['chat_id'] ?? null;

        if (empty($chatId) && !empty($validated['user_id'])) {
            $u = User::find($validated['user_id']);
            if ($u && !empty($u->telegram_chat_id)) {
                $chatId = $u->telegram_chat_id;
            } else {
                return response()->json([
                    'ok' => false,
                    'description' => 'Selected user does not have a Telegram Chat ID configured.',
                ], 422);
            }
        }

        if (empty($chatId)) {
            return response()->json([
                'ok' => false,
                'description' => 'Please provide a valid Telegram Chat ID or select a linked user.',
            ], 422);
        }

        $res = $this->telegram->sendMessage($chatId, $validated['message']);

        AuditLog::log(
            'TELEGRAM_TEST_MESSAGE',
            'telegram',
            "Sent test message to Telegram Chat ID {$chatId}",
            null,
            null,
            ['chat_id' => $chatId, 'response' => $res]
        );

        return response()->json($res);
    }

    /**
     * Set Webhook URL with full options
     */
    public function setWebhook(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'secret_token' => 'nullable|string|max:256',
            'max_connections' => 'nullable|integer|min:1|max:100',
            'drop_pending_updates' => 'nullable|boolean',
        ]);

        $res = $this->telegram->setWebhook(
            $validated['url'],
            $validated['secret_token'] ?? null,
            !empty($validated['drop_pending_updates']),
            (int)($validated['max_connections'] ?? 40)
        );

        AuditLog::log('TELEGRAM_SET_WEBHOOK', 'telegram', "Updated webhook URL to {$validated['url']}", null, null, $res);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($res);
        }

        return back()->with(
            !empty($res['ok']) ? 'success' : 'error',
            !empty($res['ok']) ? 'Telegram Webhook configured and verified successfully.' : ($res['description'] ?? 'Failed to configure webhook.')
        );
    }

    /**
     * Remove Webhook (Enables Long Polling)
     */
    public function deleteWebhook(Request $request)
    {
        $dropPending = $request->boolean('drop_pending_updates', false);
        $res = $this->telegram->deleteWebhook($dropPending);

        AuditLog::log('TELEGRAM_DELETE_WEBHOOK', 'telegram', "Removed Telegram Webhook (Switched to Polling mode)");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($res);
        }

        return back()->with(
            !empty($res['ok']) ? 'success' : 'error',
            !empty($res['ok']) ? 'Telegram Webhook removed. Long polling mode is now active.' : ($res['description'] ?? 'Failed to remove webhook.')
        );
    }

    /**
     * AJAX endpoint to fetch real-time Webhook status and diagnostics
     */
    public function webhookInfo(Request $request)
    {
        $res = $this->telegram->getWebhookInfo();
        return response()->json($res);
    }

    /**
     * Manually sync pending updates (1 Polling Cycle)
     */
    public function poll(Request $request)
    {
        $res = $this->telegram->getUpdates(0, 50, 2);

        $processed = 0;
        if (!empty($res['ok']) && !empty($res['result'])) {
            $highestId = 0;
            foreach ($res['result'] as $update) {
                $this->telegram->handleUpdate($update);
                $highestId = max($highestId, $update['update_id'] ?? 0);
                $processed++;
            }

            // Acknowledge updates by passing offset = highestId + 1
            if ($highestId > 0) {
                $this->telegram->getUpdates($highestId + 1, 1, 0);
            }
        }

        return response()->json([
            'ok' => true,
            'processed_count' => $processed,
            'message' => "Synced {$processed} new update(s) from Telegram.",
            'raw' => $res,
        ]);
    }

    /**
     * Trigger automated scheduled checks or execute on-demand reminders
     */
    public function runAutomation(Request $request)
    {
        $task = $request->query('task', 'all');
        $output = [];

        try {
            switch ($task) {
                case 'weekly_reminder':
                    \Illuminate\Support\Facades\Artisan::call('kaldis:weekly-reminder');
                    $output['weekly_reminder'] = trim(\Illuminate\Support\Facades\Artisan::output());
                    break;

                case 'deadline_check':
                    \Illuminate\Support\Facades\Artisan::call('kaldis:deadline-check');
                    $output['deadline_check'] = trim(\Illuminate\Support\Facades\Artisan::output());
                    break;

                case 'monthly_reminder':
                    \Illuminate\Support\Facades\Artisan::call('kaldis:monthly-reminder', ['--type' => 'reminder']);
                    $output['monthly_reminder'] = trim(\Illuminate\Support\Facades\Artisan::output());
                    break;

                case 'monthly_cutoff':
                    \Illuminate\Support\Facades\Artisan::call('kaldis:monthly-reminder', ['--type' => 'cutoff']);
                    $output['monthly_cutoff'] = trim(\Illuminate\Support\Facades\Artisan::output());
                    break;

                case 'poll':
                    return $this->poll($request);

                case 'all':
                default:
                    \Illuminate\Support\Facades\Artisan::call('schedule:run');
                    $output['schedule_run'] = trim(\Illuminate\Support\Facades\Artisan::output());
                    break;
            }

            return response()->json([
                'ok' => true,
                'task' => $task,
                'timestamp' => now()->toIso8601String(),
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Public Webhook Receiver for Telegram
     */
    public function webhook(Request $request)
    {
        $update = $request->all();

        if (empty($update) || !is_array($update)) {
            return response()->json(['ok' => false, 'error' => 'Invalid or empty payload'], 400);
        }

        $this->telegram->handleUpdate($update);

        return response()->json(['ok' => true]);
    }
}
