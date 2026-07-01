<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Services\Progress\ProgressService;
use Illuminate\Http\Request;

class CoachAssessmentController extends Controller
{
    public function __construct(
        protected ProgressService $service
    ) {}

    /**
     * Historial completo de evaluaciones del cliente
     */
    public function history(string $userId)
    {
        return response()->json(['success' => true, 'data' => $this->service->history((int) $userId)]);
    }

    /**
     * Última evaluación del cliente
     */
    public function latest(string $userId)
    {
        return response()->json(['success' => true, 'data' => $this->service->latest((int) $userId)]);
    }

    /**
     * Resumen de progreso del cliente
     */
    public function summary(string $userId)
    {
        return response()->json(['success' => true, 'data' => $this->service->summary((int) $userId)]);
    }

    /**
     * Datos listos para gráficas
     */
    public function charts(string $userId)
    {
        return response()->json(['success' => true, 'data' => $this->service->charts((int) $userId)]);
    }


    public function store(Request $request, string $userId)
    {
        $data = $request->validate([
            'weight'            => 'nullable|numeric',
            'height'            => 'nullable|numeric',
            'body_fat'          => 'nullable|numeric',
            'muscle_mass'       => 'nullable|numeric',
            'water_percentage'  => 'nullable|numeric',
            'bmi'               => 'nullable|numeric',
            'visceral_fat'      => 'nullable|numeric',
            'metabolic_age'     => 'nullable|integer',
            'assessment_date'   => 'nullable|date',
            'notes'             => 'nullable|string',
            'measurements'      => 'nullable|array',
            'measurements.*'    => 'nullable|numeric',
            'skinfolds'         => 'nullable|array',
            'skinfolds.*'       => 'nullable|numeric',
        ]);

        $assessment = $this->service->store((int) $userId, $request->user()->id, $data);

        return response()->json(['success' => true, 'data' => $assessment], 201);
    }

    public function update(Request $request, string $userId, string $assessmentId)
    {
        $data = $request->validate([
            'weight'            => 'nullable|numeric',
            'height'            => 'nullable|numeric',
            'body_fat'          => 'nullable|numeric',
            'muscle_mass'       => 'nullable|numeric',
            'water_percentage'  => 'nullable|numeric',
            'bmi'               => 'nullable|numeric',
            'visceral_fat'      => 'nullable|numeric',
            'metabolic_age'     => 'nullable|integer',
            'assessment_date'   => 'nullable|date',
            'notes'             => 'nullable|string',
            'measurements'      => 'nullable|array',
            'measurements.*'    => 'nullable|numeric',
            'skinfolds'         => 'nullable|array',
            'skinfolds.*'       => 'nullable|numeric',
        ]);

        $assessment = $this->service->update((int) $assessmentId, $data);

        return response()->json(['success' => true, 'data' => $assessment]);
    }

    public function destroy(string $userId, string $assessmentId)
    {
        $this->service->destroy((int) $assessmentId);

        return response()->json(['success' => true, 'data' => null]);
    }

    public function show(string $userId, string $assessmentId)
    {
        $assessment = $this->service->show((int) $assessmentId);
        return response()->json(['success' => true, 'data' => $assessment]);
    }
}