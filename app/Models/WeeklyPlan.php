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
        'notes',
        'created_by',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function tasks()
    {
        return $this->hasMany(WeeklyTask::class, 'weekly_plan_id');
    }
}
