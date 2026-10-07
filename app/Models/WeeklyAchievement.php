<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyAchievement extends Model
{
    protected $table = 'weekly_achievements';

    protected $fillable = [
        'department_id',
        'year',
        'month',
        'week_number',
        'achievement_text',
        'attachment',
        'created_by',
    ];

    public function getTitleAttribute(): ?string
    {
        return $this->achievement_text;
    }

    public function setTitleAttribute(?string $value): void
    {
        $this->attributes['achievement_text'] = $value;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->achievement_text;
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['achievement_text'] = $value;
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
