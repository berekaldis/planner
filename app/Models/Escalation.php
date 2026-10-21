<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escalation extends Model
{
    protected $table = 'escalations';
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $fillable = [
        'department_id',
        'year',
        'month',
        'week_number',
        'missed_weeks_count',
        'escalation_level',
        'recipient_role_id',
        'recipient_user_id',
        'recipients_snapshot',
        'channel',
        'status',
        'triggered_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function recipientUser()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function recipientRole()
    {
        return $this->belongsTo(Role::class, 'recipient_role_id');
    }
}
