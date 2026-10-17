<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for Kaldis Coffee PLC Telegram Bot integration.
    | Token can be defined in .env or dynamically overridden via system_settings.
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN', '8370988256:AAE8aG5s2vzEy6PfSH2b-Fiufr_num0A_rY'),
    
    'bot_username' => env('TELEGRAM_BOT_USERNAME', 'KaldisPlannerbot'),

    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', ''),

    'timeout' => env('TELEGRAM_TIMEOUT', 15),

    'notifications_enabled' => env('TELEGRAM_NOTIFICATIONS_ENABLED', true),
];
