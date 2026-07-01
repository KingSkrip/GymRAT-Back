<?php

namespace App\Http\Resources\Workout;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'goal' => $this->goal,
            'level' => $this->level,
            'coach' => $this->coach?->name,
            'client' => $this->client?->name,
            'days_per_week' => $this->days_per_week,
            'estimated_duration' => $this->estimated_duration,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'days' => WorkoutDayResource::collection($this->whenLoaded('days')),
        ];
    }
}