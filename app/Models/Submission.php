<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'submissions';

    protected $fillable = [
        'department_id',
        'year',
        'month',
        'week_number',
        'deadline_at',
        'submitted_at',
        'status',
        'submission_channel',
        'submitted_by',
        'consecutive_missed_count',
        'approval_status',
        'remarks',
        'delay_reason',
        'delay_status',
        'delay_approved_by',
        'delay_approved_at',
        'delay_gm_notes',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
            'submitted_at' => 'datetime',
            'delay_approved_at' => 'datetime',
        ];
    }

    public function getComplianceStatusAttribute(): ?string
    {
        return $this->attributes['status'] ?? null;
    }

    public function setComplianceStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = $value;
    }

    public function getSummaryAttribute(): ?string
    {
        return $this->attributes['remarks'] ?? null;
    }

    public function setSummaryAttribute(?string $value): void
    {
        $this->attributes['remarks'] = $value;
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
        return $this->delay_status === 'APPROVED' || $this->status === 'DELAY_APPROVED';
    }

    public function isDelayPending(): bool
    {
        return $this->delay_status === 'PENDING';
    }
}
