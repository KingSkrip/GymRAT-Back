<?php
// app/Http/Requests/UpdateExerciseRequest.php

namespace App\Http\Requests\Excesises;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'muscle_group'  => ['sometimes', 'required', Rule::in([
                'pecho', 'espalda', 'hombros', 'biceps', 'triceps',
                'antebrazo', 'piernas', 'gluteos', 'pantorrillas',
                'abdomen', 'cuerpo_completo', 'cardio',
            ])],
            'equipment'     => ['sometimes', 'required', Rule::in([
                'barra', 'mancuernas', 'maquina', 'cable_polea',
                'peso_corporal', 'kettlebell', 'banda_elastica',
                'trx', 'crossfit', 'otro',
            ])],
            'difficulty'    => ['sometimes', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'metric_type'   => ['sometimes', Rule::in(['reps_weight', 'reps_only', 'duration', 'distance'])],
            'description'   => ['nullable', 'string'],
            'video_url'     => ['nullable', 'url', 'max:255'],
            'image_url'     => ['nullable', 'url', 'max:255'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }
}