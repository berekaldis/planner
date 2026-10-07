<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualGoalDepartment extends Model
{
    protected $table = 'annual_goal_departments';
    public $timestamps = false;

    protected $fillable = [
        'annual_goal_id',
        'department_id',
        'is_primary',
    ];

    public function getIsPrimaryOwnerAttribute(): bool
    {
        return (bool)$this->is_primary;
    }

    public function annualGoal()
    {
        return $this->belongsTo(AnnualGoal::class, 'annual_goal_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
