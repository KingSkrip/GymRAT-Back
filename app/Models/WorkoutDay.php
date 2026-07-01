<?php
// app/Models/WorkoutDay.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutDay extends Model
{
    protected $fillable = [
        'workout_id',
        'day_number',
        'label',
        'weekdays',
        'notes',
        'order',
    ];

    protected $casts = [
        'weekdays' => 'array',
    ];

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(WorkoutDayExercise::class)->orderBy('order');
    }
}