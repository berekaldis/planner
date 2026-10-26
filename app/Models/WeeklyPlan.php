<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyPlan extends Model
{
    protected $table = 'weekly_plans';

    protected $fillable = [
        'department_id',
        'year',
        'month',
        'week_number',
        'status',
        'submitted_at',
        'submission_status',
        'notes',
        'created_by',
        'delay_reason',
        'delay_status',
        'delay_approved_by',
        'delay_approved_at',
        'delay_gm_notes',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'delay_approved_at' => 'datetime',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function tasks()
    {
        return $this->hasMany(WeeklyTask::class, 'weekly_plan_id');
    }

    public function delayApprover()
    {
        return $this->belongsTo(User::class, 'delay_approved_by');
    }

    public function isDelayApproved(): bool
    {
        return $this->delay_status === 'APPROVED' || $this->submission_status === 'DELAY_APPROVED';
    }

    public function isDelayPending(): bool
    {
        return $this->delay_status === 'PENDING';
    }
}
