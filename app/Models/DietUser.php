<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietUser extends Model
{

    protected $table = 'diets_user';
    protected $fillable = [
        'coach_id',
        'user_id',
        'title',
        'description',
        'calories',
        'protein',
        'carbs',
        'fat',
        'water',
        'starts_at',
        'ends_at',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean'
    ];

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function meals()
    {
        return $this->hasMany(DietMealUser::class, 'diet_id');
    }
}
