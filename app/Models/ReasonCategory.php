<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReasonCategory extends Model
{
    protected $table = 'reason_categories';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_active',
        'display_order',
    ];

    public function getCategoryNameAttribute(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function setCategoryNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
    }

    public function taskResults()
    {
        return $this->hasMany(WeeklyTaskResult::class, 'not_done_reason_id');
    }
}
