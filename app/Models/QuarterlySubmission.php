<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuarterlySubmission extends Model
{
    protected $table = 'quarterly_submissions';

    protected $fillable = [
        'department_id',
        'year',
        'quarter',
        'months',
        'total_plans',
        'done_plans',
        'not_done_plans',
        'completion_percentage',
        'status',
        'approval_status',
        'submission_channel',
        'submitted_by',
        'submitted_at',
        'executive_summary',
        'challenges',
        'next_quarter_priorities',
        'gm_feedback',
        'gm_feedback_at',
        'gm_reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'completion_percentage' => 'decimal:2',
            'total_plans' => 'integer',
            'done_plans' => 'integer',
            'not_done_plans' => 'integer',
            'submitted_at' => 'datetime',
            'gm_feedback_at' => 'datetime',
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'gm_reviewed_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'SUBMITTED' => '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check-circle me-1"></i> Submitted</span>',
            'LATE' => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="fas fa-clock me-1"></i> Late</span>',
            default => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="fas fa-file-pen me-1"></i> Draft</span>',
        };
    }

    public function getApprovalBadgeAttribute(): string
    {
        return match ($this->approval_status) {
            'APPROVED' => '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-circle-check me-1"></i> Approved</span>',
            'REVISION_REQUESTED' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="fas fa-triangle-exclamation me-1"></i> Revision Requested</span>',
            default => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="fas fa-hourglass-half me-1"></i> Under Review</span>',
        };
    }
}
