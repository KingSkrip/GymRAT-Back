<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoachUser extends Model
{
    use HasFactory;

    protected $table = 'coach_user';

    protected $fillable = [
        'coach_id',
        'user_id'
    ];
}