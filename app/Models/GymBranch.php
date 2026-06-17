<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'gym_id',
        'name',
        'address',
        'phone',
        'latitude',
        'longitude',
        'is_active'
    ];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'gym_branch_id');
    }

    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class, 'gym_branch_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(
            GymBranchSubscription::class
        );
    }
}