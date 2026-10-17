<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlySubmission extends Model
{
    protected $table = 'monthly_submissions';

    protected $fillable = [
        'department_id',
        'year',
        'month',
        'deadline_at',
        'submitted_at',
        'status',
        'submission_channel',
        'submitted_by',
        'approval_status',
        'gm_feedback',
        'gm_feedback_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
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
}
