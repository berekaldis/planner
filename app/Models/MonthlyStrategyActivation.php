<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyStrategyActivation extends Model
{
    protected $table = 'monthly_strategy_activations';
    const CREATED_AT = 'activated_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'annual_goal_id',
        'department_id',
        'year',
        'month',
        'active',
        'activated_by',
        'activated_at',
        'updated_by',
        'updated_at',
    ];

    public function getIsActiveAttribute(): bool
    {
        return $this->active === 'YES';
    }

    public function annualGoal()
    {
        return $this->belongsTo(AnnualGoal::class, 'annual_goal_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function activator()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }
}
