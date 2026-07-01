<?php

namespace App\Services\Progress;

use App\Models\AssessmentUser;

class ProgressService
{
    /**
     * Historial completo del cliente
     */
    public function history(int $userId)
    {
        return AssessmentUser::with([
            'measurements',
            'skinfolds',
            'photos',
            'coach'
        ])
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Última evaluación
     */
    public function latest(int $userId)
    {
        return AssessmentUser::with([
            'measurements',
            'skinfolds',
            'photos',
            'coach'
        ])
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * Datos para gráficas
     */
    public function charts(int $userId): array
    {
        $history = AssessmentUser::where('user_id', $userId)
            ->orderBy('created_at')
            ->get();

        return [

            'weight' => $history->map(function ($item) {
                return [
                    'date' => $item->created_at->format('Y-m-d'),
                    'value' => $item->weight
                ];
            }),

            'body_fat' => $history->map(function ($item) {
                return [
                    'date' => $item->created_at->format('Y-m-d'),
                    'value' => $item->body_fat
                ];
            }),

            'muscle_mass' => $history->map(function ($item) {
                return [
                    'date' => $item->created_at->format('Y-m-d'),
                    'value' => $item->muscle_mass
                ];
            }),

            'water_percentage' => $history->map(function ($item) {
                return [
                    'date' => $item->created_at->format('Y-m-d'),
                    'value' => $item->water_percentage
                ];
            }),

            'bmi' => $history->map(function ($item) {
                return [
                    'date' => $item->created_at->format('Y-m-d'),
                    'value' => $item->bmi
                ];
            }),

            'visceral_fat' => $history->map(function ($item) {
                return [
                    'date' => $item->created_at->format('Y-m-d'),
                    'value' => $item->visceral_fat
                ];
            }),

        ];
    }

    /**
     * Resumen del progreso
     */
    public function summary(int $userId): array
    {
        $history = AssessmentUser::where('user_id', $userId)
            ->orderBy('created_at')
            ->get();

        if ($history->count() < 2) {

            return [
                'current' => $history->last(),
                'changes' => null
            ];
        }

        $first = $history->first();
        $last = $history->last();

        return [

            'current' => $last,

            'changes' => [

                'weight' => $last->weight - $first->weight,

                'body_fat' => $last->body_fat - $first->body_fat,

                'muscle_mass' => $last->muscle_mass - $first->muscle_mass,

                'water_percentage' => $last->water_percentage - $first->water_percentage,

                'bmi' => $last->bmi - $first->bmi,

                'visceral_fat' => $last->visceral_fat - $first->visceral_fat,

            ]

        ];
    }


    /**
     * Crear evaluación con medidas y pliegues opcionales
     */
    public function store(int $userId, int $coachId, array $data): AssessmentUser
    {
        $assessment = AssessmentUser::create([
            'user_id'           => $userId,
            'coach_id'          => $coachId,
            'weight'            => $data['weight']           ?? null,
            'height'            => $data['height']           ?? null,
            'body_fat'          => $data['body_fat']         ?? null,
            'muscle_mass'       => $data['muscle_mass']      ?? null,
            'water_percentage'  => $data['water_percentage'] ?? null,
            'bmi'               => $data['bmi']              ?? null,
            'visceral_fat'      => $data['visceral_fat']     ?? null,
            'metabolic_age'     => $data['metabolic_age']    ?? null,
            'assessment_date'   => $data['assessment_date']  ?? now()->toDateString(),
            'notes'             => $data['notes']            ?? null,
        ]);

        if (!empty($data['measurements'])) {
            $assessment->measurements()->create(
                array_filter($data['measurements'], fn($v) => $v !== null)
            );
        }

        if (!empty($data['skinfolds'])) {
            $assessment->skinfolds()->create(
                array_filter($data['skinfolds'], fn($v) => $v !== null)
            );
        }

        return $assessment->load(['measurements', 'skinfolds', 'photos', 'coach']);
    }

    /**
     * Actualizar evaluación existente
     */
    public function update(int $assessmentId, array $data): AssessmentUser
    {
        $assessment = AssessmentUser::findOrFail($assessmentId);

        $assessment->update(array_filter([
            'weight'           => $data['weight']           ?? null,
            'height'           => $data['height']           ?? null,
            'body_fat'         => $data['body_fat']         ?? null,
            'muscle_mass'      => $data['muscle_mass']      ?? null,
            'water_percentage' => $data['water_percentage'] ?? null,
            'bmi'              => $data['bmi']              ?? null,
            'visceral_fat'     => $data['visceral_fat']     ?? null,
            'metabolic_age'    => $data['metabolic_age']    ?? null,
            'assessment_date'  => $data['assessment_date']  ?? null,
            'notes'            => $data['notes']            ?? null,
        ], fn($v) => $v !== null));

        if (!empty($data['measurements'])) {
            $assessment->measurements()->updateOrCreate(
                ['assessment_id' => $assessment->id],
                array_filter($data['measurements'], fn($v) => $v !== null)
            );
        }

        if (!empty($data['skinfolds'])) {
            $assessment->skinfolds()->updateOrCreate(
                ['assessment_id' => $assessment->id],
                array_filter($data['skinfolds'], fn($v) => $v !== null)
            );
        }

        return $assessment->load(['measurements', 'skinfolds', 'photos', 'coach']);
    }

    /**
     * Eliminar evaluación y sus relaciones (cascade por FK)
     */
    public function destroy(int $assessmentId): void
    {
        AssessmentUser::findOrFail($assessmentId)->delete();
    }

    public function show(int $assessmentId): AssessmentUser
    {
        return AssessmentUser::with(['measurements', 'skinfolds', 'photos', 'coach'])
            ->findOrFail($assessmentId);
    }
}