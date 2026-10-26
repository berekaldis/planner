<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyReport extends Model
{
    protected $table = 'weekly_reports';

    protected $fillable = [
        'department_id',
        'year',
        'month',
        'week_number',
        'status',
        'total_tasks',
        'done_tasks',
        'not_done_tasks',
        'completion_percentage',
        'submitted_by',
        'submitted_at',
        'submission_status',
        'notes',
        'gm_feedback',
        'gm_feedback_at',
        'approval_status',
        'delay_reason',
        'delay_status',
        'delay_approved_by',
        'delay_approved_at',
        'delay_gm_notes',
    ];

    protected function casts(): array
    {
        return [
            'completion_percentage' => 'decimal:2',
            'submitted_at' => 'datetime',
            'gm_feedback_at' => 'datetime',
            'delay_approved_at' => 'datetime',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
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
