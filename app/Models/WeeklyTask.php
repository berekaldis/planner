<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyTask extends Model
{
    protected $table = 'weekly_tasks';

    protected $fillable = [
        'weekly_plan_id',
        'monthly_plan_id',
        'department_id',
        'year',
        'month',
        'week_number',
        'plan_type',
        'task_type',
        'annual_goal_id',
        'task_title',
        'task_description',
        'expected_result',
        'responsible_person',
        'priority',
        'due_date',
        'dependency',
        'notes',
        'gm_comment',
        'gm_comment_at',
        'gm_comment_by',
        'approval_status',
        'decline_reason',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    public function weeklyPlan()
    {
        return $this->belongsTo(WeeklyPlan::class, 'weekly_plan_id');
    }

    public function monthlyPlan()
    {
        return $this->belongsTo(MonthlyPlan::class, 'monthly_plan_id');
    }

    public function annualGoal()
    {
        return $this->belongsTo(AnnualGoal::class, 'annual_goal_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function result()
    {
        return $this->hasOne(WeeklyTaskResult::class, 'weekly_task_id');
    }

    public function challenges()
    {
        return $this->hasMany(WeeklyChallenge::class, 'related_weekly_task_id');
    }

    protected function casts(): array
    {
        return [
            'gm_comment_at' => 'datetime',
            'approved_at' => 'datetime',
            'due_date' => 'date',
        ];
    }

    public function gmCommenter()
    {
        return $this->belongsTo(User::class, 'gm_comment_by');
    }

    public function getTaskTargetAttribute(): ?string
    {
        return $this->expected_result;
    }

    public function getAssignedToAttribute(): ?string
    {
        return $this->responsible_person;
    }
}
