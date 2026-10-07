<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';
    public $timestamps = false;
    protected $primaryKey = 'setting_key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_group',
        'description',
        'is_public',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::find($key);
        return $setting ? $setting->setting_value : $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['setting_key' => $key], ['setting_value' => $value]);
    }
}
