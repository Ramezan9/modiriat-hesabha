<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'pin',
        'fingerprint_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'fingerprint_enabled' => 'boolean',
        ];
    }

    public function workspaceMembers()
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function workspaces()
    {
        return $this->belongsToMany(
            Workspace::class,
            'workspace_members'
        )->withPivot([
            'role',
            'status',
        ])->withTimestamps();
    }
}
