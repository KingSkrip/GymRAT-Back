<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_active',
        'subscription_start',
        'subscription_end',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscription_start' => 'date',
        'subscription_end' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gyms()
    {
        return $this->hasMany(Gym::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }
}