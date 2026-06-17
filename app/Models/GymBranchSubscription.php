<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymBranchSubscription extends Model
{
    protected $fillable = [
        'gym_branch_id',
        'plan',
        'price',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at'   => 'date',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(GymBranch::class, 'gym_branch_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            GymBranchPayment::class,
            'gym_branch_subscription_id'
        );
    }
}