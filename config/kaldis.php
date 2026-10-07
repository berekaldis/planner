<?php

return [
    'company_name' => 'Kaldis Coffee PLC',
    'app_name' => 'Kaldis Strategic Planning & Performance Management System',
    'app_short_name' => 'Kaldis PMS',
    'current_planning_year' => env('KALDIS_PLANNING_YEAR', '2019 E.C.'),
    'current_planning_month' => env('KALDIS_PLANNING_MONTH', 'Nehase'),
    'weekly_deadline_day' => env('KALDIS_WEEKLY_DEADLINE_DAY', 'Monday'),
    'weekly_deadline_time' => env('KALDIS_WEEKLY_DEADLINE_TIME', '12:00'),
    'ethiopian_months' => [
        'Meskerem', 'Tikimt', 'Hidar', 'Tahsas', 'Tir', 'Yakatit',
        'Magabit', 'Miyazya', 'Ginbot', 'Sene', 'Hamle', 'Nehase'
    ],
    'planning_years' => [
        '2019 E.C.', '2020 E.C.', '2021 E.C.', '2022 E.C.', '2023 E.C.',
        '2024 E.C.', '2025 E.C.', '2026 E.C.', '2027 E.C.', '2028 E.C.',
        '2029 E.C.', '2030 E.C.'
    ],
    'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'telegram_bot_username' => env('TELEGRAM_BOT_USERNAME', 'KaldisPlannerBot'),
];
