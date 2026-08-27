<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'invite_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Workspace $workspace) {
            if (empty($workspace->invite_code)) {
                $workspace->invite_code = strtoupper(
                    Str::random(8)
                );
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(
            User::class,
            'owner_id'
        );
    }

    public function members()
    {
        return $this->hasMany(
            WorkspaceMember::class
        );
    }

    public function customers()
    {
        return $this->hasMany(
            Customer::class
        );
    }

    public function transactions()
    {
        return $this->hasMany(
            Transaction::class
        );
    }
}
