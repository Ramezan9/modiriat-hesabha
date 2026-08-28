<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'city',
        'profile_photo',
        'fingerprint_enabled',
    ];

    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'fingerprint_enabled' => 'boolean',
        'password' => 'hashed',
        'pin' => 'hashed',
    ];

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(
            Workspace::class,
            'owner_id'
        );
    }

    public function workspaceMembers(): HasMany
    {
        return $this->hasMany(
            WorkspaceMember::class
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            Transaction::class
        );
    }
}
