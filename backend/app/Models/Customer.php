<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function workspace()
    {
        return $this->belongsTo(
            Workspace::class,
            'workspace_id'
        );
    }

    public function transactions()
    {
        return $this->hasMany(
            Transaction::class
        );
    }

    public function receipts()
    {
        return $this->hasMany(
            Receipt::class
        );
    }
}
