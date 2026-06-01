<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'price',
        'start_date',
        'end_date',
        'is_active'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔥 calcular si está activa
    public function isValid()
    {
        return $this->is_active && now()->lt($this->end_date);
    }

    public function remainingDays()
    {
        return now()->diffInDays($this->end_date, false);
    }
}