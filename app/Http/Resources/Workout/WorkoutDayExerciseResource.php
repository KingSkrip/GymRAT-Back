<?php

namespace App\Http\Resources\Workout;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutDayExerciseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'exercise_id' => $this->exercise_id,
            'name' => $this->name,
            'sets' => $this->sets,
            'reps' => $this->reps,
            'weight_kg' => $this->weight_kg,
            'duration_sec' => $this->duration_sec,
            'distance_m' => $this->distance_m,
            'note' => $this->note,
            'order' => $this->order,
            'metric_type' => $this->exercise?->metric_type,
        ];
    }
}