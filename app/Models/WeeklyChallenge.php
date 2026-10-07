<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyChallenge extends Model
{
    protected $table = 'weekly_challenges';

    protected $fillable = [
        'department_id',
        'year',
        'month',
        'week_number',
        'challenge_text',
        'related_weekly_task_id',
        'attachment',
        'created_by',
    ];

    public function getTitleAttribute(): ?string
    {
        return $this->challenge_text;
    }

    public function setTitleAttribute(?string $value): void
    {
        $this->attributes['challenge_text'] = $value;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->challenge_text;
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['challenge_text'] = $value;
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function relatedTask()
    {
        return $this->belongsTo(WeeklyTask::class, 'related_weekly_task_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
