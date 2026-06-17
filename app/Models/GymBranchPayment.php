<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GymBranchPayment extends Model
{
    protected $fillable = [
        'gym_branch_subscription_id',
        'amount',
        'status',
        'payment_method',
        'transaction_id',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            GymBranchSubscription::class,
            'gym_branch_subscription_id'
        );
    }
}