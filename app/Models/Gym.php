<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gym extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_client_id',
        'name',
        'address',
        'phone',
        'is_active'
    ];

    public function client()
    {
        return $this->belongsTo(SystemClient::class, 'system_client_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function branches()
    {
        return $this->hasMany(GymBranch::class);
    }
}