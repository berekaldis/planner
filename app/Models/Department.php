<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = [
        'department_name',
        'department_code',
        'head_user_id',
        'active',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function getStatusAttribute(): string
    {
        return ($this->attributes['active'] ?? 1) ? 'ACTIVE' : 'INACTIVE';
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['active'] = in_array($value, ['ACTIVE', '1', 1, true], true) ? 1 : 0;
    }

    public function head()
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }

    public function annualGoals()
    {
        return $this->belongsToMany(
            AnnualGoal::class,
            'annual_goal_departments',
            'department_id',
            'annual_goal_id'
        )->withPivot('is_primary');
    }

    public function monthlyPlans()
    {
        return $this->hasMany(MonthlyPlan::class, 'department_id');
    }

    public function weeklyReports()
    {
        return $this->hasMany(WeeklyReport::class, 'department_id');
    }
}
