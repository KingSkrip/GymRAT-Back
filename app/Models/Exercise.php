<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'muscle_group',
        'equipment',
        'difficulty',
        'metric_type',
        'description',
        'video_url',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMuscleGroup($query, string $muscleGroup)
    {
        return $query->where('muscle_group', $muscleGroup);
    }

    public function scopeEquipment($query, string $equipment)
    {
        return $query->where('equipment', $equipment);
    }

    /**
     * Convierte el ejercicio del catálogo al shape que se guarda
     * dentro del JSON `exercises` de la tabla `workouts`.
     * El coach/usuario le agrega sets, reps, weight_kg, etc. desde el front.
     */
    public function toWorkoutPayload(array $overrides = []): array
    {
        $base = match ($this->metric_type) {
            'duration'    => ['sets' => 1, 'reps' => 1, 'duration_sec' => 60],
            'distance'    => ['sets' => 1, 'reps' => 1, 'distance_m' => 0],
            'reps_only'   => ['sets' => 3, 'reps' => 10, 'weight_kg' => 0],
            default       => ['sets' => 3, 'reps' => 10, 'weight_kg' => 0],
        };

        return array_merge(
            ['name' => $this->name],
            $base,
            $overrides
        );
    }
}