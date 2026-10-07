<?php

if (!function_exists('kaldis_setting')) {
    function kaldis_setting(string $key, $default = null)
    {
        try {
            $setting = \Illuminate\Support\Facades\DB::table('system_settings')
                ->where('setting_key', $key)
                ->value('setting_value');
            return $setting !== null ? $setting : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('format_et_calendar')) {
    function format_et_calendar(?string $month = null, ?string $year = null): string
    {
        $m = $month ?? kaldis_setting('current_planning_month', config('kaldis.current_planning_month'));
        $y = $year ?? kaldis_setting('current_planning_year', config('kaldis.current_planning_year'));
        return "{$m} {$y}";
    }
}
