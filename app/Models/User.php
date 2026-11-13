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
        'company_user_id',
        'username',
        'email',
        'password_hash',
        'full_name',
        'role_id',
        'department_id',
        'telegram_chat_id',
        'telegram_username',
        'is_active',
        'must_change_password',
        'password_changed_at',
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
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function requiresPasswordChange(): bool
    {
        return (bool)($this->must_change_password ?? false);
    }

    public function getNameAttribute(): string
    {
        return !empty($this->full_name) ? $this->full_name : ($this->username ?? '');
    }

    public function getStatusAttribute(): string
    {
        return $this->is_active ? 'ACTIVE' : 'INACTIVE';
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
        return false;
    }

    public function isViewer(): bool
    {
        return $this->role && in_array(strtolower($this->role->name), ['viewer', 'user']);
    }

    public function canAccessAllDepartments(): bool
    {
        return $this->isSuperAdmin() || $this->isGM();
    }
}