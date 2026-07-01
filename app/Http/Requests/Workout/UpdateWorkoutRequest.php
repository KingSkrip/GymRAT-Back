<?php
// app/Http/Requests/UpdateWorkoutRequest.php

namespace App\Http\Requests\Workout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coach_id'            => ['nullable', 'exists:users,id'],
            'user_id'             => ['nullable', 'exists:users,id'],
            'title'               => ['nullable', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'goal'                => ['sometimes', 'required', Rule::in([
                'Fuerza',
                'Hipertrofia',
                'Pérdida de grasa',
                'Acondicionamiento físico',
                'Rehabilitación',
                'Personalizado',
            ])],
            'level'               => ['sometimes', 'required', Rule::in(['Principiante', 'Intermedio', 'Avanzado'])],
            'days_per_week'       => ['nullable', 'integer', 'min:1', 'max:7'],
            'estimated_duration'  => ['nullable', 'integer', 'min:1'],
            'starts_at'           => ['nullable', 'date'],
            'ends_at'             => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active'           => ['sometimes', 'boolean'],

            // Si mandan `days`, se reemplaza el set completo (ver Service)
            'days'                          => ['sometimes', 'array', 'min:1'],
            'days.*.id'                     => ['nullable', 'integer', 'exists:workout_days,id'],
            'days.*.day_number'             => ['required_with:days', 'integer', 'min:1'],
            'days.*.label'                  => ['nullable', 'string', 'max:255'],
            'days.*.weekdays'               => ['nullable', 'array'],
            'days.*.weekdays.*'             => ['integer', 'min:1', 'max:7'],
            'days.*.notes'                  => ['nullable', 'string'],
            'days.*.order'                  => ['nullable', 'integer'],

            'days.*.exercises'                    => ['required_with:days', 'array', 'min:1'],
            'days.*.exercises.*.id'               => ['nullable', 'integer', 'exists:workout_day_exercises,id'],
            'days.*.exercises.*.exercise_id'      => ['nullable', 'exists:exercises,id'],
            'days.*.exercises.*.name'             => ['required_with:days.*.exercises', 'string', 'max:255'],
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