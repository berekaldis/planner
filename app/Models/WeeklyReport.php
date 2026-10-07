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
    ];

    protected function casts(): array
    {
        return [
            'completion_percentage' => 'decimal:2',
            'submitted_at' => 'datetime',
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
