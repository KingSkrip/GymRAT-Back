<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'is_active',
        'subscription_start',
        'subscription_end'
    ];

    public function gyms()
    {
        return $this->hasMany(Gym::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }
}