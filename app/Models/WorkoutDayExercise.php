<?php
// app/Models/WorkoutDayExercise.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutDayExercise extends Model
{
    protected $fillable = [
        'workout_day_id',
        'exercise_id',
        'name',
        'sets',
        'reps',
        'weight_kg',
        'duration_sec',
        'distance_m',
        'note',
        'order',
    ];

    public function workoutDay(): BelongsTo
    {
        return $this->belongsTo(WorkoutDay::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}