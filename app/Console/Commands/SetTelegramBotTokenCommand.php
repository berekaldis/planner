<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SetTelegramBotTokenCommand extends Command
{
    protected $signature = 'kaldis:set-bot-token {token : Telegram Bot API token from @BotFather}';
    protected $description = 'Set, verify, and persist the Telegram Bot API token in both system settings and .env';

    public function handle(): int
    {
        $token = trim($this->argument('token'));

        if (empty($token) || !preg_match('/^\d+:[A-Za-z0-9_-]{20,}$/', $token)) {
            $this->error("Invalid Telegram bot token format. Tokens are typically formatted as '123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ'.");
            return Command::FAILURE;
        }

        $this->info("Verifying token with Telegram Bot API...");
        $service = new TelegramService($token);
        $me = $service->getMe();

        if (empty($me['ok'])) {
            $desc = $me['description'] ?? 'Unauthorized or network error';
            $this->error("Telegram verification failed: {$desc}");
            $this->line("Please verify you copied the full active token from @BotFather.");
            return Command::FAILURE;
        }

        $botUsername = $me['result']['username'] ?? 'KaldisPlannerBot';
        $botName = $me['result']['first_name'] ?? 'Kaldis Bot';

        $this->info("Verified successfully! Connected to @{$botUsername} ({$botName}).");

        // 1. Persist to system_settings
        SystemSetting::set('telegram_bot_token', $token, 'TELEGRAM', 'Telegram Bot API Token (from @BotFather)');
        SystemSetting::set('telegram_bot_username', $botUsername, 'TELEGRAM', 'Telegram Bot Username');

        // 2. Persist to .env if writable
        $this->updateEnvFile('TELEGRAM_BOT_TOKEN', $token);
        $this->updateEnvFile('TELEGRAM_BOT_USERNAME', $botUsername);

        AuditLog::log('UPDATE_TELEGRAM_TOKEN', 'telegram', "Updated Telegram Bot Token to @{$botUsername}");

        $this->info("Bot token saved and activated in System Settings and .env!");
        return Command::SUCCESS;
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
}
