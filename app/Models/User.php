<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'full_name',
        'role_id',
        'department_id',
        'telegram_chat_id',
        'telegram_username',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = \Illuminate\Support\Facades\Hash::make($value);
    }

    public function getPasswordAttribute()
    {
        return $this->password_hash;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role && in_array(strtolower($this->role->name), ['super_admin', 'it_admin']);
    }

    public function isGM(): bool
    {
        return $this->role && in_array(strtolower($this->role->name), ['gm_management', 'gm', 'general_manager']);
    }

    public function isDeptHead(): bool
    {
        return $this->role && in_array(strtolower($this->role->name), ['dept_head', 'department_head']);
    }

    public function isHROfficer(): bool
    {
        return $this->role && in_array(strtolower($this->role->name), ['hr', 'hr_officer']);
    }

    public function isViewer(): bool
    {
        return $this->role && in_array(strtolower($this->role->name), ['viewer', 'user']);
    }

    public function canAccessAllDepartments(): bool
    {
        return $this->isSuperAdmin() || $this->isGM() || $this->isHROfficer();
    }
}
