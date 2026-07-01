<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Services\Progress\ProgressService;
use Illuminate\Http\Request;

class CoachProgressController extends Controller
{
    public function __construct(
        protected ProgressService $service
    ) {}

    /**
     * Historial completo del cliente
     */
    public function history(int $userId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->history($userId)
        ]);
    }

    /**
     * Resumen del progreso
     */
    public function summary(int $userId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->summary($userId)
        ]);
    }

    /**
     * Datos para gráficas
     */
    public function charts(int $userId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->charts($userId)
        ]);
    }

    /**
     * Última evaluación
     */
    public function latest(int $userId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->latest($userId)
        ]);
    }
}