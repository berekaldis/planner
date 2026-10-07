<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualGoal extends Model
{
    protected $table = 'annual_goals';

    protected $fillable = [
        'goal_code',
        'title',
        'definition_of_done',
        'responsible_department_id',
        'annual_target',
        'priority',
        'notes',
        'status',
        'year',
        'created_by',
    ];

    public function getGoalTitleAttribute(): ?string
    {
        return $this->attributes['title'] ?? null;
    }

    public function setGoalTitleAttribute(?string $value): void
    {
        $this->attributes['title'] = $value;
    }

    public function getPlanningYearAttribute(): ?string
    {
        return $this->attributes['year'] ?? null;
    }

    public function setPlanningYearAttribute(?string $value): void
    {
        $this->attributes['year'] = $value;
    }

    public function getTargetDescriptionAttribute(): ?string
    {
        return $this->attributes['annual_target'] ?? null;
    }

    public function departments()
    {
        return $this->belongsToMany(
            Department::class,
            'annual_goal_departments',
            'annual_goal_id',
            'department_id'
        )->withPivot('is_primary');
    }

    public function monthlyActivations()
    {
        return $this->hasMany(MonthlyStrategyActivation::class, 'annual_goal_id');
    }

    public function monthlyPlans()
    {
        return $this->hasMany(MonthlyPlan::class, 'annual_goal_id');
    }

    public function isActivatedFor(string $year, string $month): bool
    {
        return $this->monthlyActivations()
            ->where('year', $year)
            ->where('month', $month)
            ->where('active', 'YES')
            ->exists();
    }
}
