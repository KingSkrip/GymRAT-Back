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
        'gymbranch_id',
        'name',
        'phone',
        'email',
        'password',
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

    public function roles()
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles');
    }

    public function branch()
    {
        return $this->belongsTo(GymBranch::class, 'gymbranch_id');
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


    public function modelHasRole()
    {
        return $this->morphOne(ModelHasRole::class, 'model');
    }

    public function modelHasRoles()
    {
        return $this->morphMany(ModelHasRole::class, 'model');
    }

    // Helper para checar rol fácil
    public function hasRole(string $roleName): bool
    {
        return $this->modelHasRoles()
            ->whereHas('role', fn($q) => $q->where('name', $roleName))
            ->exists();
    }
    public function ownedGyms()
    {
        return $this->hasMany(Gym::class, 'owner_id');
    }


    public function systemClient()
    {
        return $this->hasOne(SystemClient::class);
    }

    public function membership()
    {
        return $this->hasOne(Membership::class)->latestOfMany();
    }

    public function coaches()
    {
        return $this->belongsToMany(User::class, 'coach_user', 'user_id', 'coach_id');
    }

    public function assignedClients()
    {
        return $this->belongsToMany(User::class, 'coach_user', 'coach_id', 'user_id');
    }



    public function workouts()
    {
        return $this->hasMany(Workout::class, 'user_id');
    }

    public function createdWorkouts()
    {
        return $this->hasMany(Workout::class, 'coach_id');
    }

    public function assessments()
    {
        return $this->hasMany(AssessmentUser::class, 'user_id');
    }

    public function createdAssessments()
    {
        return $this->hasMany(AssessmentUser::class, 'coach_id');
    }

    public function diets()
    {
        return $this->hasMany(DietUser::class, 'user_id');
    }

    public function createdDiets()
    {
        return $this->hasMany(DietUser::class, 'coach_id');
    }
}
