<?php
// app/Http/Requests/StoreExerciseRequest.php

namespace App\Http\Requests\Excesises;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta según tu auth/policies
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'muscle_group'  => ['required', Rule::in([
                'pecho',
                'espalda',
                'hombros',
                'biceps',
                'triceps',
                'antebrazo',
                'piernas',
                'gluteos',
                'pantorrillas',
                'abdomen',
                'cuerpo_completo',
                'cardio',
            ])],
            'equipment'     => ['required', Rule::in([
                'barra',
                'mancuernas',
                'maquina',
                'cable_polea',
                'peso_corporal',
                'kettlebell',
                'banda_elastica',
                'trx',
                'crossfit',
                'otro',
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