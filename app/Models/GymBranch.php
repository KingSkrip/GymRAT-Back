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

    // 🏋️ pertenece a un gym
    public function gym()
    {
        return $this->belongsTo(Gym::class, 'gym_id');
    }

    // 👤 usuarios dentro de esta sucursal
    public function users()
    {
        return $this->hasMany(User::class, 'gym_branch_id');
    }

    // 📊 logs de acceso de esta sucursal
    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class, 'gym_branch_id');
    }

    // 💳 suscripciones de la sucursal
    public function subscriptions(): HasMany
    {
        return $this->hasMany(GymBranchSubscription::class, 'gym_branch_id');
    }
}