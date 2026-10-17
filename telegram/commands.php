<?php
/**
 * Telegram Bot Command Handlers
 * Kaldis Coffee PLC
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/telegram.php';

require_once __DIR__ . '/../includes/functions.php';

class TelegramCommandHandler {
    private PDO $db;
    private TelegramNotifier $notifier;

    public function __construct(PDO $db, TelegramNotifier $notifier) {
        $this->db = $db;
        $this->notifier = $notifier;
    }

    /**
     * Handle incoming message object from Telegram
     */
    /**
     * Handle incoming message object from Telegram
     */
    public function handle(array $message): void {
        try {
            $chatId = $message['chat']['id'] ?? null;
            $text = trim($message['text'] ?? ($message['caption'] ?? ''));
            $username = $message['from']['username'] ?? '';
            $firstName = $message['from']['first_name'] ?? 'User';

            if (!$chatId) return;

            // Try to identify user by telegram_chat_id or username
            $user = $this->identifyUser((string)$chatId, $username);

            // Parse command (Strip @BotName suffix if present e.g. /start@KaldisPlannerBot -> /start)
            $parts = explode(' ', $text, 2);
            $rawCmd = strtolower(trim($parts[0] ?? ''));
            $command = strtolower(explode('@', $rawCmd)[0]);
            $argument = trim($parts[1] ?? '');

            // Command routing
            switch ($command) {
                case '/start':
                    $this->cmdStart($chatId, $firstName, $user);
                    break;

                case '/status':
                    $this->cmdStatus($chatId, $user);
                    break;

                case '/myreport':
                    $this->cmdMyReport($chatId, $user);
                    break;

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
                    $this->cmdHelp($chatId);
                    break;
            }
        } catch (\Throwable $e) {
            error_log("TelegramCommandHandler handle error: " . $e->getMessage());
        }
    }

    private function identifyUser(string $chatId, string $username): ?array {
        // First check by telegram_chat_id in users table
        $stmt = $this->db->prepare("
            SELECT u.*, d.department_name, d.department_code, r.name as role_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.telegram_chat_id = ? AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$chatId]);
        $user = $stmt->fetch();

        // Fallback by username or email match
        if (!$user && !empty($username)) {
            $cleanUsername = ltrim($username, '@');
            $stmt2 = $this->db->prepare("
                SELECT u.*, d.department_name, d.department_code, r.name as role_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE (u.username = ? OR u.email LIKE ?) AND u.is_active = 1
                LIMIT 1
            ");
            $stmt2->execute([$cleanUsername, $cleanUsername . '@%']);
            $user = $stmt2->fetch();

            if ($user && empty($user['telegram_chat_id'])) {
                // Automatically link telegram_chat_id for future fast lookups
                $this->db->prepare("UPDATE users SET telegram_chat_id = ? WHERE id = ?")->execute([$chatId, $user['id']]);
                $user['telegram_chat_id'] = $chatId;
            }
        }

        return $user ?: null;
    }

    private function cmdStart(string|int $chatId, string $firstName, ?array $user): void {
        $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        if ($user) {
            $safeFullName = htmlspecialchars($user['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
            $safeDeptName = htmlspecialchars($user['department_name'] ?? 'Department', ENT_QUOTES, 'UTF-8');
            $safeDeptCode = htmlspecialchars($user['department_code'] ?? 'DEPT', ENT_QUOTES, 'UTF-8');

            $msg = "☕ <b>Welcome, {$safeFullName}!</b>\n\n"
                 . "You are connected as <b>{$safeDeptName} ({$safeDeptCode})</b>.\n\n"
                 . "Use this bot to monitor your weekly tasks, log achievements/challenges, and track reporting deadlines.\n\n"
                 . "<b>Available Commands:</b>\n"
                 . "/status — Check current week's plan &amp; submission status\n"
                 . "/myreport — View your latest submitted report\n"
                 . "/achievement &lt;text&gt; — Log a weekly achievement\n"
                 . "/challenge &lt;text&gt; — Log a weekly challenge\n"
                 . "/help — Show commands list";
        } else {
            $msg = "☕ <b>Welcome to Kaldis Coffee Planning Bot!</b>\n\n"
                 . "Hello {$safeFirstName}. Your Telegram account is not yet bound to a Department profile.\n\n"
                 . "Your Telegram Chat ID is: <code>{$chatId}</code>\n\n"
                 . "Please contact your IT Administrator or set your Chat ID in User Accounts settings to link your profile.";
        }
        $this->notifier->sendMessage($chatId, $msg);
    }

    private function cmdStatus(string|int $chatId, ?array $user): void {
        if (!$user || empty($user['department_id'])) {
            $this->notifier->sendMessage($chatId, "❌ Your account is not mapped to an active department. Please contact IT.");
            return;
        }

        $period = get_ethiopian_period();
        $year = $period['year'];
        $month = $period['month'];
        $week = $period['week'];

        // Fetch task counts
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(wt.id) as total,
                SUM(CASE WHEN wtr.result = 'DONE' THEN 1 ELSE 0 END) as done,
                SUM(CASE WHEN wtr.result = 'NOT_DONE' THEN 1 ELSE 0 END) as not_done
            FROM weekly_tasks wt
            LEFT JOIN weekly_task_results wtr ON wt.id = wtr.weekly_task_id
            WHERE wt.department_id = ? AND wt.year = ? AND wt.month = ? AND wt.week_number = ?
        ");
        $stmt->execute([$user['department_id'], $year, $month, $week]);
        $stats = $stmt->fetch();

        // Fetch submission status
        $subStmt = $this->db->prepare("
            SELECT status, submitted_at FROM weekly_reports 
            WHERE department_id = ? AND year = ? AND month = ? AND week_number = ?
        ");
        $subStmt->execute([$user['department_id'], $year, $month, $week]);
        $sub = $subStmt->fetch();

        $total = (int)($stats['total'] ?? 0);
        $done = (int)($stats['done'] ?? 0);
        $notDone = (int)($stats['not_done'] ?? 0);
        $pct = $total > 0 ? round(($done / $total) * 100, 1) : 0;
        $statusStr = ($sub && $sub['status'] === 'SUBMITTED') ? "✅ SUBMITTED" : "⏳ PENDING / IN DRAFT";

        $msg = "📊 <b>WEEKLY STATUS: {$user['department_code']}</b>\n"
             . "Period: {$month} {$year} (Week {$week})\n\n"
             . "• <b>Planned Tasks:</b> {$total}\n"
             . "• <b>DONE:</b> {$done}\n"
             . "• <b>NOT DONE:</b> {$notDone}\n"
             . "• <b>Completion:</b> {$pct}%\n"
             . "• <b>Report Status:</b> {$statusStr}\n\n"
             . "Deadline: Monday 12:00 PM.";

        $this->notifier->sendMessage($chatId, $msg);
    }

    private function cmdMyReport(string|int $chatId, ?array $user): void {
        if (!$user || empty($user['department_id'])) {
            $this->notifier->sendMessage($chatId, "❌ You do not have permission to view department reports.");
            return;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM weekly_reports 
            WHERE department_id = ? 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$user['department_id']]);
        $rep = $stmt->fetch();

        if (!$rep) {
            $this->notifier->sendMessage($chatId, "No submitted reports found in your department's archive.");
            return;
        }

        $msg = "📋 <b>LATEST SUBMITTED REPORT &mdash; {$user['department_code']}</b>\n\n"
             . "Period: {$rep['month']} {$rep['year']} (Week {$rep['week_number']})\n"
             . "Total Tasks: {$rep['total_tasks']}\n"
             . "DONE: {$rep['done_tasks']}\n"
             . "NOT DONE: {$rep['not_done_tasks']}\n"
             . "Completion: <b>{$rep['completion_percentage']}%</b>\n"
             . "Compliance: <b>{$rep['submission_status']}</b>\n"
             . "Submitted At: {$rep['submitted_at']}";

        $this->notifier->sendMessage($chatId, $msg);
    }

    private function cmdReport(string|int $chatId, ?array $user): void {
        $msg = "📝 <b>Weekly Performance Reporting:</b>\n\n"
             . "Department Heads must evaluate every planned task (DONE or NOT DONE with explanation) on the Kaldis portal.\n\n"
             . "🔗 Access Portal: <a href=\"https://planner.kaldiscoffee.com/Planner/\">Kaldis Planning Portal</a>\n\n"
             . "Deadline: Every Monday at 12:00 PM.";
        $this->notifier->sendMessage($chatId, $msg);
    }

    private function cmdAchievement(string|int $chatId, ?array $user, string $text): void {
        if (!$user || empty($user['department_id'])) {
            $this->notifier->sendMessage($chatId, "❌ Unrecognized department head.");
            return;
        }

        if (empty($text)) {
            $this->notifier->sendMessage($chatId, "⚠️ Please provide your achievement description.\nExample: <code>/achievement Completed hardware PM for 5 branches</code>");
            return;
        }

        $period = get_ethiopian_period();
        $year = $period['year'];
        $month = $period['month'];
        $week = $period['week'];

        $stmt = $this->db->prepare("
            INSERT INTO weekly_achievements (department_id, year, month, week_number, achievement_text, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user['department_id'], $year, $month, $week, $text, $user['id']]);

        $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $this->notifier->sendMessage($chatId, "🏆 <b>Achievement Recorded!</b>\n\n\"{$safeText}\"\nHas been added to Week {$week}'s report.");
    }

    private function cmdChallenge(string|int $chatId, ?array $user, string $text): void {
        if (!$user || empty($user['department_id'])) {
            $this->notifier->sendMessage($chatId, "❌ Unrecognized department head.");
            return;
        }

        if (empty($text)) {
            $this->notifier->sendMessage($chatId, "⚠️ Please provide your challenge description.\nExample: <code>/challenge Vendor quotation delayed by 3 days</code>");
            return;
        }

        $period = get_ethiopian_period();
        $year = $period['year'];
        $month = $period['month'];
        $week = $period['week'];

        $stmt = $this->db->prepare("
            INSERT INTO weekly_challenges (department_id, year, month, week_number, challenge_text, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user['department_id'], $year, $month, $week, $text, $user['id']]);

        $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $this->notifier->sendMessage($chatId, "⚠️ <b>Challenge Recorded!</b>\n\n\"{$safeText}\"\nHas been logged for management review.");
    }

    private function cmdHelp(string|int $chatId): void {
        $msg = "🤖 <b>Kaldis Coffee Planning Bot Commands:</b>\n\n"
             . "/start — Welcome &amp; Account Status\n"
             . "/status — Check current week's execution &amp; reporting status\n"
             . "/myreport — View your latest submitted report\n"
             . "/report — Submission guide &amp; portal link\n"
             . "/achievement &lt;text&gt; — Record an achievement directly\n"
             . "/challenge &lt;text&gt; — Record a challenge directly\n"
             . "/help — Show this help menu";
        $this->notifier->sendMessage($chatId, $msg);

    }
}
