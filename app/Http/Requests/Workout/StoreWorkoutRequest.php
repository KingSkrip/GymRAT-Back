<?php
// app/Http/Requests/StoreWorkoutRequest.php

namespace App\Http\Requests\Workout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta según tu auth/policies
    }

    public function rules(): array
    {
        return [
            'coach_id'            => ['nullable', 'exists:users,id'],
            'user_id'             => ['nullable', 'exists:users,id'],
            'title'               => ['nullable', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'goal'                => ['required', Rule::in([
                'Fuerza',
                'Hipertrofia',
                'Pérdida de grasa',
                'Acondicionamiento físico',
                'Rehabilitación',
                'Personalizado',
            ])],
            'level'               => ['required', Rule::in(['Principiante', 'Intermedio', 'Avanzado'])],
            'days_per_week'       => ['nullable', 'integer', 'min:1', 'max:7'],
            'estimated_duration'  => ['nullable', 'integer', 'min:1'],
            'starts_at'           => ['nullable', 'date'],
            'ends_at'             => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active'           => ['sometimes', 'boolean'],

            // Días de la rutina
            'days'                          => ['required', 'array', 'min:1'],
            'days.*.day_number'             => ['required', 'integer', 'min:1'],
            'days.*.label'                  => ['nullable', 'string', 'max:255'],
            'days.*.weekdays'               => ['nullable', 'array'],
            'days.*.weekdays.*'             => ['integer', 'min:1', 'max:7'],
            'days.*.notes'                  => ['nullable', 'string'],
            'days.*.order'                  => ['nullable', 'integer'],

            // Ejercicios por día
            'days.*.exercises'                    => ['required', 'array', 'min:1'],
            'days.*.exercises.*.exercise_id'      => ['nullable', 'exists:exercises,id'],
            'days.*.exercises.*.name'             => ['required', 'string', 'max:255'],
            'days.*.exercises.*.sets'             => ['nullable', 'integer', 'min:1'],
            'days.*.exercises.*.reps'             => ['nullable', 'string', 'max:50'],
            'days.*.exercises.*.weight_kg'        => ['nullable', 'numeric', 'min:0'],
            'days.*.exercises.*.duration_sec'     => ['nullable', 'integer', 'min:0'],
            'days.*.exercises.*.distance_m'       => ['nullable', 'integer', 'min:0'],
            'days.*.exercises.*.note'             => ['nullable', 'string', 'max:255'],
            'days.*.exercises.*.order'            => ['nullable', 'integer'],
        ];
    }
}