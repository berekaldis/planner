<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll {--once : Run a single update cycle and exit} {--timeout=10 : Polling timeout in seconds}';
    protected $description = 'Listen for and process Telegram Bot updates via Long Polling';

    public function handle(TelegramService $telegram): int
    {
        if (!$telegram->isConfigured()) {
            $this->error("Telegram Bot Token is not configured. Please set it in System Settings or .env.");
            return 1;
        }

        $bot = $telegram->getMe();
        if (empty($bot['ok'])) {
            $this->error("Failed to connect to Telegram Bot API: " . ($bot['description'] ?? 'Unknown error'));
            return 1;
        }

        $botName = $bot['result']['first_name'] ?? 'Bot';
        $botUsername = $bot['result']['username'] ?? '';

        $this->info("====================================================");
        $this->info(" Kaldis Coffee Telegram Bot Runner (Polling Mode)");
        $this->info(" Connected as: {$botName} (@{$botUsername})");
        $this->info("====================================================");

        // Delete webhook first to avoid 409 Conflict
        $this->line("Removing webhook to ensure polling mode...");
        $telegram->deleteWebhook();

        $offset = 0;
        $isOnce = $this->option('once');
        $timeout = (int)$this->option('timeout');

        $this->info("Listening for Telegram messages... " . ($isOnce ? "[Single Cycle]" : "[Press Ctrl+C to stop]"));

        do {
            try {
                $response = $telegram->getUpdates($offset, 50, $timeout);

                if (!empty($response['ok']) && !empty($response['result'])) {
                    foreach ($response['result'] as $update) {
                        $offset = ($update['update_id'] ?? $offset) + 1;

                        if (isset($update['message'])) {
                            $msg = $update['message'];
                            $from = $msg['from']['first_name'] ?? 'User';
                            $text = $msg['text'] ?? '[File/Media]';
                            $chatId = $msg['chat']['id'] ?? '';

                            $this->line("[" . now()->format('H:i:s') . "] Message from {$from} ({$chatId}): {$text}");

                            $telegram->handleUpdate($update);
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->warn("Error during polling cycle: " . $e->getMessage());
            }

            if (!$isOnce) {
                sleep(1);
            }
        } while (!$isOnce);

        $this->info("Telegram polling cycle completed.");
        return 0;
    }
}
