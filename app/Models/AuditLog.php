<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function log(string $action, string $module, ?string $details = null, $recordId = null, $old = null, $new = null): self
    {
        $user = auth()->user();
        $newPayload = $new;
        if ($details && empty($new)) {
            $newPayload = ['description' => $details];
        }

        return static::create([
            'user_id' => $user ? $user->id : null,
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId ? (string)$recordId : null,
            'old_values' => is_array($old) ? json_encode($old) : (is_string($old) ? $old : null),
            'new_values' => is_array($newPayload) ? json_encode($newPayload) : (is_string($newPayload) ? $newPayload : null),
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);
    }
}
