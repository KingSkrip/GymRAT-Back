<?php

namespace App\Http\Resources\Workout;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutDayResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'day_number' => $this->day_number,
            'label' => $this->label,
            'weekdays' => $this->weekdays,
            'notes' => $this->notes,
            'order' => $this->order,
            'exercises' => WorkoutDayExerciseResource::collection($this->whenLoaded('exercises')),
        ];
    }
}