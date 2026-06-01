<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'gym_id',
        'name',
        'email',
        'password',
        'type',
        'is_active'
    ];

    protected $hidden = [
        'password'
    ];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function coachClients()
    {
        return $this->belongsToMany(
            User::class,
            'coach_user',
            'coach_id',
            'user_id'
        );
    }

    public function workouts()
    {
        return $this->hasMany(Workout::class, 'user_id');
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles');
    }

    public function branch()
    {
        return $this->belongsTo(GymBranch::class, 'gym_branch_id');
    }

    public function biometrics()
    {
        return $this->hasMany(Biometric::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class);
    }

    public function qrTokens()
    {
        return $this->hasMany(QrToken::class);
    }


    public function suscriptions_user()
    {
        return $this->hasMany(ClientSubscription::class);
    }
}