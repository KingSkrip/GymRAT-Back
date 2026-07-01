<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietMealUser extends Model
{
    protected $table = 'diet_meals_user';
    protected $fillable = [
        'diet_id',
        'meal',
        'food',
        'quantity',
        'unit',
        'order'
    ];

    public function diet()
    {
        return $this->belongsTo(DietUser::class);
    }
}