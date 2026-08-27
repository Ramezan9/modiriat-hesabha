<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'phone',
        'city',
        'profile_photo',
        'is_pinned',
        'is_active',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(
            Workspace::class
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            Transaction::class
        );
    }
}
