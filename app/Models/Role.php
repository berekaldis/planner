<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
