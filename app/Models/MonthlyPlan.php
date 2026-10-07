<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyPlan extends Model
{
    protected $table = 'monthly_plans';

    protected $fillable = [
        'department_id',
        'year',
        'month',
        'plan_type',
        'annual_goal_id',
        'title',
        'description',
        'definition_of_done',
        'monthly_target',
        'target_percentage',
        'priority',
        'dependency',
        'responsible_person',
        'notes',
        'active',
        'created_by',
    ];

    public function getStatusAttribute(): string
    {
        return ($this->attributes['active'] ?? 1) ? 'ACTIVE' : 'INACTIVE';
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['active'] = in_array($value, ['ACTIVE', '1', 1, true], true) ? 1 : 0;
    }

    protected function casts(): array
    {
        return [
            'target_percentage' => 'decimal:2',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function annualGoal()
    {
        return $this->belongsTo(AnnualGoal::class, 'annual_goal_id');
    }

    public function weeklyTasks()
    {
        return $this->hasMany(WeeklyTask::class, 'monthly_plan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isStrategy(): bool
    {
        return $this->plan_type === 'STRATEGY';
    }

    public function isOperational(): bool
    {
        return $this->plan_type === 'OPERATIONAL';
    }
}
