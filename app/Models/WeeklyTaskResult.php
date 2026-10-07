<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyTaskResult extends Model
{
    protected $table = 'weekly_task_results';

    protected $fillable = [
        'weekly_task_id',
        'department_id',
        'year',
        'month',
        'week_number',
        'result',
        'completed_at',
        'completed_by',
        'not_done_reason_id',
        'not_done_explanation',
        'next_action',
        'expected_completion_date',
        'attachment',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'expected_completion_date' => 'date',
        ];
    }

    public function task()
    {
        return $this->belongsTo(WeeklyTask::class, 'weekly_task_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function reasonCategory()
    {
        return $this->belongsTo(ReasonCategory::class, 'not_done_reason_id');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['result'] ?? null;
    }

    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['result'] = $value;
    }

    public function isDone(): bool
    {
        return $this->result === 'DONE';
    }

    public function isNotDone(): bool
    {
        return $this->result === 'NOT_DONE';
    }
}
