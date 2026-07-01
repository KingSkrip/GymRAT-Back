<?php

namespace App\Http\Resources\Diet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DietResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'title' => $this->title,
            'description' => $this->description,

            'coach' => $this->coach?->name,
            'client' => $this->client?->name,

            'is_active' => $this->is_active,

            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,

            'created_at' => $this->created_at,

            'meals' => $this->meals?->map(function ($meal) {
                return [
                    'id' => $meal->id,
                    'meal' => $meal->meal,        // desayuno, comida, cena, etc
                    'food' => $meal->food,
                    'quantity' => $meal->quantity,
                    'unit' => $meal->unit,
                    'order' => $meal->order,
                ];
            }),
        ];
    }
}