<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Submission;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WeeklyAchievement;
use App\Models\WeeklyChallenge;
use App\Models\WeeklyTask;
use Carbon\Carbon;
use CURLFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $botToken;
    private string $apiUrl;

    public function __construct(?string $token = null)
    {
        if ($token !== null) {
            $this->botToken = trim($token);
        } else {
            $envToken = trim((string)env('TELEGRAM_BOT_TOKEN', config('telegram.bot_token', '')));
            $dbToken = null;
            try {
                $dbToken = SystemSetting::where('setting_key', 'telegram_bot_token')->value('setting_value');
                $dbToken = !empty($dbToken) ? trim((string)$dbToken) : null;
            } catch (\Throwable $e) {}

            $revokedTokens = [
                '8370988256:AAE8aG5s2vzEy6PfSH2b-Fiufr_num0A_rY',
                'YOUR_BOT_TOKEN_HERE',
                'YOUR_TELEGRAM_BOT_TOKEN',
            ];

            // If .env has a real configured token not in revoked list, prioritize it
            if (!empty($envToken) && !in_array($envToken, $revokedTokens)) {
                $this->botToken = $envToken;
                if ($dbToken !== $envToken) {
                    try {
                        SystemSetting::set('telegram_bot_token', $envToken, 'TELEGRAM', 'Telegram Bot API Token (from @BotFather)');
                    } catch (\Throwable $e) {}
                }
            } elseif (!empty($dbToken) && !in_array($dbToken, $revokedTokens)) {
                $this->botToken = $dbToken;
            } else {
                $this->botToken = !empty($envToken) ? $envToken : ($dbToken ?? '');
            }
        }

        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";
    }

    public function isConfigured(): bool
    {
        return !empty($this->botToken);
    }

    public function getBotToken(): string
    {
        return $this->botToken ?? '';
    }

    /**
     * Low-level HTTP request to Telegram Bot API
     */
    public function request(string $method, array $params = [], bool $isMultipart = false): array
    {
        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'description' => 'Telegram Bot Token is not configured in System Settings or .env',
            ];
        }

        $url = $this->apiUrl . $method;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, config('telegram.timeout', 15));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (!empty($params)) {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($isMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error("Telegram API Curl Error on {$method}: {$error}");
            return ['ok' => false, 'description' => "cURL Error: {$error}"];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            Log::error("Telegram API Invalid JSON on {$method} (HTTP {$httpCode}): {$response}");
            return ['ok' => false, 'description' => "Invalid response (HTTP {$httpCode})", 'raw' => $response];
        }

        return $decoded;
    }

    /**
     * Test connection and retrieve bot profile (getMe)
     */
    public function getMe(): array
    {
        return $this->request('getMe');
    }

    /**
     * Retrieve webhook configuration & delivery status
     */
    public function getWebhookInfo(): array
    {
        return $this->request('getWebhookInfo');
    }

    /**
     * Set Webhook URL with full configuration options
     */
    public function setWebhook(string $url, ?string $secretToken = null, bool $dropPendingUpdates = false, int $maxConnections = 40): array
    {
        $params = [
            'url' => $url,
            'drop_pending_updates' => $dropPendingUpdates,
            'max_connections' => max(1, min(100, $maxConnections)),
        ];

        if (!empty($secretToken)) {
            $params['secret_token'] = $secretToken;
        }

        $res = $this->request('setWebhook', $params);
        if (!empty($res['ok'])) {
            try {
                SystemSetting::set('telegram_webhook_url', $url, 'TELEGRAM', 'Current active Telegram Webhook URL');
                if (!empty($secretToken)) {
                    SystemSetting::set('telegram_webhook_secret', $secretToken, 'TELEGRAM', 'Webhook secret token');
                }
            } catch (\Throwable $e) {}
        }
        return $res;
    }

    /**
     * Delete active webhook (required to use getUpdates long polling)
     */
    public function deleteWebhook(bool $dropPendingUpdates = false): array
    {
        $res = $this->request('deleteWebhook', ['drop_pending_updates' => $dropPendingUpdates]);
        if (!empty($res['ok'])) {
            try {
                SystemSetting::set('telegram_webhook_url', '', 'TELEGRAM', 'Current active Telegram Webhook URL');
                SystemSetting::set('telegram_webhook_secret', '', 'TELEGRAM', 'Webhook secret token');
            } catch (\Throwable $e) {}
        }
        return $res;
    }

    /**
     * Fetch incoming updates manually (Long Polling)
     */
    public function getUpdates(int $offset = 0, int $limit = 50, int $timeout = 5): array
    {
        return $this->request('getUpdates', [
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => $timeout,
        ]);
    }

    /**
     * Send HTML formatted message to a chat ID
     */
    public function sendMessage(string|int $chatId, string $text, ?array $replyMarkup = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if (!empty($replyMarkup)) {
            if (isset($replyMarkup['inline_keyboard']) || isset($replyMarkup['keyboard']) || isset($replyMarkup['remove_keyboard']) || isset($replyMarkup['force_reply'])) {
                $params['reply_markup'] = json_encode($replyMarkup);
            } else {
                if (isset($replyMarkup['parse_mode'])) {
                    $params['parse_mode'] = $replyMarkup['parse_mode'];
                }
                if (isset($replyMarkup['disable_web_page_preview'])) {
                    $params['disable_web_page_preview'] = (bool)$replyMarkup['disable_web_page_preview'];
                }
            }
        }

        $res = $this->request('sendMessage', $params);

        // Fallback: If Telegram API rejects message due to unescaped HTML entities (e.g. unescaped & in department names), retry sending without parse_mode
        if (empty($res['ok']) && !empty($res['description']) && str_contains(strtolower($res['description']), "can't parse entities")) {
            Log::warning("Telegram HTML parse error: {$res['description']}. Retrying with plain text delivery to {$chatId}.");
            $plainParams = $params;
            $plainParams['text'] = strip_tags($text);
            unset($plainParams['parse_mode']);
            $res = $this->request('sendMessage', $plainParams);
        }

        // Audit log in telegram_messages table
        try {
            DB::table('telegram_messages')->insert([
                'message_id' => $res['result']['message_id'] ?? 0,
                'telegram_user_id' => (string)$chatId,
                'message_text' => $text,
                'direction' => 'OUTGOING',
                'status' => !empty($res['ok']) ? 'SENT' : 'FAILED',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Could not log outgoing telegram message: " . $e->getMessage());
        }

        return $res;
    }

    /**
     * Send Document / PDF report to a chat ID
     */
    public function sendDocument(string|int $chatId, string $filePath, string $caption = ''): array
    {
        if (!file_exists($filePath)) {
            return ['ok' => false, 'description' => "File not found: {$filePath}"];
        }

        $params = [
            'chat_id' => $chatId,
            'caption' => $caption,
            'document' => new CURLFile($filePath),
            'parse_mode' => 'HTML',
        ];

        $res = $this->request('sendDocument', $params, true);

        try {
            DB::table('telegram_messages')->insert([
                'message_id' => $res['result']['message_id'] ?? 0,
                'telegram_user_id' => (string)$chatId,
                'message_text' => "[Document] " . basename($filePath) . ($caption ? " - {$caption}" : ""),
                'direction' => 'OUTGOING',
                'status' => !empty($res['ok']) ? 'SENT' : 'FAILED',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {}

        return $res;
    }

    /**
     * Process an incoming Telegram update payload
     */
    public function handleUpdate(array $update): void
    {
        if (!isset($update['message'])) {
            return;
        }

        $msg = $update['message'];
        $chatId = $msg['chat']['id'] ?? null;
        $text = trim($msg['text'] ?? '');
        $username = $msg['from']['username'] ?? '';
        $firstName = $msg['from']['first_name'] ?? 'Colleague';

        if (!$chatId) {
            return;
        }

        // Log incoming message to telegram_messages table
        try {
            DB::table('telegram_messages')->insert([
                'message_id' => $msg['message_id'] ?? 0,
                'telegram_user_id' => (string)$chatId,
                'message_text' => $text ?: '[Non-text update]',
                'direction' => 'INCOMING',
                'status' => 'PROCESSED',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {}

        // Identify matched system user
        $user = $this->identifyUser((string)$chatId, $username);

        // Record/Update in telegram_users table
        try {
            DB::table('telegram_users')->updateOrInsert(
                ['telegram_user_id' => (string)$chatId],
                [
                    'username' => $username,
                    'first_name' => $firstName,
                    'last_name' => $msg['from']['last_name'] ?? '',
                    'user_id' => $user ? $user->id : null,
                    'department_id' => $user ? $user->department_id : null,
                    'is_active' => 1,
                    'updated_at' => now(),
                ]
            );
        } catch (\Throwable $e) {}

        // Parse command & arguments
        $parts = explode(' ', $text, 2);
        $command = strtolower($parts[0] ?? '');
        $argument = trim($parts[1] ?? '');

        switch ($command) {
            case '/start':
                $this->cmdStart($chatId, $firstName, $user);
                break;

            case '/status':
                $this->cmdStatus($chatId, $user);
                break;

            case '/myreport':
            case '/report':
                $this->cmdReport($chatId, $user);
                break;

            case '/achievement':
                $this->cmdAchievement($chatId, $user, $argument);
                break;

            case '/challenge':
                $this->cmdChallenge($chatId, $user, $argument);
                break;

            case '/help':
            default:
                $this->cmdHelp($chatId, $user);
                break;
        }
    }

    /**
     * Identify a registered user by Telegram Chat ID or Username
     */
    public function identifyUser(string $chatId, string $username = ''): ?User
    {
        // 1. Direct match by telegram_chat_id
        $user = User::with(['department', 'role'])
            ->where('telegram_chat_id', $chatId)
            ->where('is_active', 1)
            ->first();

        if ($user) return $user;

        // 2. Match by telegram_username if provided
        if (!empty($username)) {
            $cleanUser = ltrim($username, '@');
            $user = User::with(['department', 'role'])
                ->where(function ($q) use ($cleanUser) {
                    $q->where('telegram_username', $cleanUser)
                      ->orWhere('telegram_username', '@' . $cleanUser)
                      ->orWhere('telegram_chat_id', '@' . $cleanUser);
                })
                ->where('is_active', 1)
                ->first();

            // Auto-bind chat ID for future instant match
            if ($user && empty($user->telegram_chat_id)) {
                $user->update(['telegram_chat_id' => $chatId]);
            }

            if ($user) return $user;
        }

        return null;
    }

    /**
     * /start Command Handler
     */
    private function cmdStart(int|string $chatId, string $firstName, ?User $user): void
    {
        if ($user) {
            $deptName = $user->department ? $user->department->department_name : 'Corporate Management';
            $roleName = $user->role ? ($user->role->display_name ?? $user->role->name) : 'User';

            $msg = "☕ <b>Welcome to Kaldis Coffee Planning Portal</b>\n\n";
            $msg .= "Hello <b>{$firstName}</b>!\n";
            $msg .= "👤 Account: <b>{$user->full_name}</b> (@{$user->username})\n";
            $msg .= "🏢 Department: <b>{$deptName}</b>\n";
            $msg .= "🛡️ Role: <b>{$roleName}</b>\n\n";
            $msg .= "✅ <i>Your Telegram account is successfully linked to Kaldis Planner.</i>\n\n";
            $msg .= "<b>Available Commands:</b>\n";
            $msg .= "📊 /status — View weekly tasks & execution progress\n";
            $msg .= "📑 /report — Check weekly report submission status\n";
            $msg .= "🏆 /achievement &lt;text&gt; — Log a weekly achievement\n";
            $msg .= "⚠️ /challenge &lt;text&gt; — Log a weekly blocker or issue\n";
            $msg .= "❓ /help — How to use this bot";
        } else {
            $msg = "☕ <b>Kaldis Coffee PLC — Planning & Reporting Bot</b>\n\n";
            $msg .= "Hello <b>{$firstName}</b>!\n";
            $msg .= "Your Telegram Chat ID is: <code>{$chatId}</code>\n\n";
            $msg .= "⚠️ <i>Your Telegram account is not yet bound to a portal user.</i>\n\n";
            $msg .= "<b>How to Link Your Account:</b>\n";
            $msg .= "1. Log in to the Kaldis Planning Portal.\n";
            $msg .= "2. Go to <b>User Accounts</b> or ask your IT Administrator.\n";
            $msg .= "3. Enter your Chat ID <code>{$chatId}</code> into your user profile.\n\n";
            $msg .= "Once linked, you will receive weekly deadline reminders and can submit achievements directly here!";
        }

        $this->sendMessage($chatId, $msg);
    }

    /**
     * /status Command Handler
     */
    private function cmdStatus(int|string $chatId, ?User $user): void
    {
        if (!$user) {
            $this->sendMessage($chatId, "⚠️ Please bind your Telegram Chat ID <code>{$chatId}</code> in the portal first.");
            return;
        }

        $year = kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $week = 1; // Default current week

        $dept = $user->department;
        if (!$dept) {
            // GM/Admin view
            $totalDepts = Department::where('active', 1)->count();
            $totalTasks = WeeklyTask::where('year', $year)->where('month', $month)->count();
            $doneTasks = DB::table('weekly_tasks as wt')
                ->join('weekly_task_results as wtr', 'wt.id', '=', 'wtr.weekly_task_id')
                ->where('wt.year', $year)->where('wt.month', $month)->where('wtr.result', 'DONE')
                ->count();

            $rate = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100, 1) : 0;

            $msg = "📊 <b>Company Performance Status ({$month} {$year})</b>\n\n";
            $msg .= "🏢 Active Departments: <b>{$totalDepts}</b>\n";
            $msg .= "📝 Scheduled Tasks: <b>{$totalTasks}</b>\n";
            $msg .= "✅ Completed Tasks: <b>{$doneTasks}</b>\n";
            $msg .= "📈 Overall Completion Rate: <b>{$rate}%</b>\n\n";
            $msg .= "Use the web portal for complete department breakdowns and executive scorecards.";

            $this->sendMessage($chatId, $msg);
            return;
        }

        $tasks = WeeklyTask::with('result')
            ->where('department_id', $dept->id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('week_number', $week)
            ->get();

        $total = $tasks->count();
        $done = $tasks->filter(fn($t) => $t->result && $t->result->result === 'DONE')->count();
        $notDone = $tasks->filter(fn($t) => $t->result && $t->result->result === 'NOT_DONE')->count();
        $pending = $total - ($done + $notDone);
        $rate = $total > 0 ? round(($done / $total) * 100, 1) : 0;

        $msg = "📊 <b>Department Performance ({$dept->department_code})</b>\n";
        $msg .= "📅 Period: <b>{$month} {$year} — Week {$week}</b>\n\n";
        $msg .= "• Total Tasks: <b>{$total}</b>\n";
        $msg .= "• ✅ DONE: <b>{$done}</b>\n";
        $msg .= "• ❌ NOT DONE: <b>{$notDone}</b>\n";
        $msg .= "• ⏳ Pending Evaluation: <b>{$pending}</b>\n";
        $msg .= "• 🎯 Progress Rate: <b>{$rate}%</b>\n";

        $this->sendMessage($chatId, $msg);
    }

    /**
     * /report Command Handler
     */
    private function cmdReport(int|string $chatId, ?User $user): void
    {
        if (!$user || !$user->department) {
            $this->sendMessage($chatId, "⚠️ Please access the portal to view full company-wide reports.");
            return;
        }

        $dept = $user->department;
        $year = kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $week = 1;

        $sub = Submission::where('department_id', $dept->id)
            ->where('year', $year)
            ->where('month', $month)
            ->where('week_number', $week)
            ->first();

        $statusText = $sub ? ($sub->status === 'ON_TIME' ? '✅ ON TIME' : ($sub->status === 'LATE' ? '⚠️ LATE' : $sub->status)) : '⏳ NOT YET SUBMITTED';

        $msg = "📑 <b>Weekly Report Status — {$dept->department_name}</b>\n";
        $msg .= "📅 Week {$week} ({$month} {$year})\n\n";
        $msg .= "Status: <b>{$statusText}</b>\n";
        if ($sub && $sub->submitted_at) {
            $msg .= "Submitted At: <b>" . Carbon::parse($sub->submitted_at)->format('M d, Y h:i A') . "</b>\n";
        }
        $msg .= "\n⏰ Regular weekly submission deadline is <b>Monday 6:30 LT (12:30 PM)</b>.";

        $this->sendMessage($chatId, $msg);
    }

    /**
     * /achievement <text> Command Handler
     */
    private function cmdAchievement(int|string $chatId, ?User $user, string $text): void
    {
        if (!$user || !$user->department) {
            $this->sendMessage($chatId, "⚠️ Only registered department personnel can log weekly achievements.");
            return;
        }

        if (empty($text)) {
            $this->sendMessage($chatId, "ℹ️ <b>Usage:</b>\n<code>/achievement Completed setup of POS terminals across 5 branches without downtime</code>");
            return;
        }

        $year = kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $week = 1;

        WeeklyAchievement::create([
            'department_id' => $user->department_id,
            'year' => $year,
            'month' => $month,
            'week_number' => $week,
            'achievement_text' => $text,
            'created_by' => $user->id,
        ]);

        $msg = "🏆 <b>Weekly Achievement Logged!</b>\n\n";
        $msg .= "🏢 Department: <b>{$user->department->department_code}</b>\n";
        $msg .= "📝 <i>\"{$text}\"</i>\n\n";
        $msg .= "✅ This achievement is now visible in the company-wide feed.";

        $this->sendMessage($chatId, $msg);
    }

    /**
     * /challenge <text> Command Handler
     */
    private function cmdChallenge(int|string $chatId, ?User $user, string $text): void
    {
        if (!$user || !$user->department) {
            $this->sendMessage($chatId, "⚠️ Only registered department personnel can log weekly challenges.");
            return;
        }

        if (empty($text)) {
            $this->sendMessage($chatId, "ℹ️ <b>Usage:</b>\n<code>/challenge Delay in network router delivery from supplier</code>");
            return;
        }

        $year = kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $week = 1;

        WeeklyChallenge::create([
            'department_id' => $user->department_id,
            'year' => $year,
            'month' => $month,
            'week_number' => $week,
            'challenge_text' => $text,
            'created_by' => $user->id,
        ]);

        $msg = "⚠️ <b>Weekly Challenge / Blocker Logged</b>\n\n";
        $msg .= "🏢 Department: <b>{$user->department->department_code}</b>\n";
        $msg .= "📝 <i>\"{$text}\"</i>\n\n";
        $msg .= "📢 General Management and cross-functional teams have been alerted.";

        $this->sendMessage($chatId, $msg);
    }

    /**
     * /help Command Handler
     */
    private function cmdHelp(int|string $chatId, ?User $user): void
    {
        $msg = "📖 <b>Kaldis Coffee Planning Bot Guide</b>\n\n";
        $msg .= "<b>Available Commands:</b>\n";
        $msg .= "• /start — Welcome message & account binding status\n";
        $msg .= "• /status — Check weekly progress & completion stats\n";
        $msg .= "• /report — Check submission status for the current week\n";
        $msg .= "• /achievement &lt;text&gt; — Submit an independent weekly achievement\n";
        $msg .= "• /challenge &lt;text&gt; — Submit an organizational challenge or blocker\n";
        $msg .= "• /help — Show this help message\n\n";
        $msg .= "For full planning, GM activations, and evaluations, visit the web portal at:\n";
        $msg .= "<code>http://localhost/Planner</code>";

        $this->sendMessage($chatId, $msg);
    }

    /**
     * Send Weekly Reminder Notification
     */
    public function sendWeeklyReminder(Department $department, int $weekNumber): bool
    {
        $head = $department->head;
        if (!$head || empty($head->telegram_chat_id)) {
            return false;
        }

        $year = kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));
        $deadlineDay = kaldis_setting('weekly_deadline_day', 'Monday');
        $deadlineTime = kaldis_setting('weekly_deadline_time', '12:30');

        $text = "☕ <b>Kaldis Coffee PLC — Weekly Report Reminder</b>\n\n";
        $text .= "Dear <b>{$head->full_name}</b> ({$department->department_name}),\n\n";
        $text .= "This is a reminder to finalize and submit your department's <b>Week {$weekNumber}</b> Performance Report for <b>{$month} {$year}</b>.\n\n";
        $text .= "⏰ <b>Submission Deadline:</b> {$deadlineDay} at 6:30 LT ({$deadlineTime})\n";
        $text .= "🌐 Please evaluate all tasks as <b>DONE</b> or <b>NOT DONE</b> in the portal:\n";
        $text .= "<code>http://localhost/Planner/weekly/performance</code>";

        $res = $this->sendMessage($head->telegram_chat_id, $text);
        return !empty($res['ok']);
    }

    /**
     * Send Escalation Alert Notification
     */
    public function sendEscalationAlert(Department $department, int $missedWeeks, string $level): bool
    {
        $head = $department->head;
        $chatIds = [];

        if ($head && !empty($head->telegram_chat_id)) {
            $chatIds[] = $head->telegram_chat_id;
        }

        // Also notify GM for Level 2 and Level 3
        if (in_array($level, ['LEVEL_2', 'LEVEL_3', 'LEVEL_4'])) {
            $gm = User::whereHas('role', fn($q) => $q->whereIn('name', ['gm_management', 'gm']))->first();
            if ($gm && !empty($gm->telegram_chat_id) && !in_array($gm->telegram_chat_id, $chatIds)) {
                $chatIds[] = $gm->telegram_chat_id;
            }
        }

        if (empty($chatIds)) {
            return false;
        }

        $year = kaldis_setting('current_planning_year', config('kaldis.current_planning_year', '2019 E.C.'));
        $month = kaldis_setting('current_planning_month', config('kaldis.current_planning_month', 'Meskerem'));

        $text = "🚨 <b>Kaldis Coffee PLC — Report Escalation Alert</b>\n\n";
        $text .= "⚠️ <b>Department:</b> {$department->department_name} ({$department->department_code})\n";
        $text .= "🔴 <b>Escalation Level:</b> {$level}\n";
        $text .= "📉 <b>Missed Submission Weeks:</b> {$missedWeeks} week(s)\n";
        $text .= "📅 <b>Planning Period:</b> {$month} {$year}\n\n";
        $text .= "Immediate submission or formal explanation is required.\n";
        $text .= "Access Portal: <code>http://localhost/Planner</code>";

        $success = false;
        foreach ($chatIds as $cid) {
            $res = $this->sendMessage($cid, $text);
            if (!empty($res['ok'])) $success = true;
        }

        return $success;
    }
}
