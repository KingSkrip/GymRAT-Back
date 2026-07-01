<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Services\Progress\Photos\PhotoService;
use Illuminate\Http\Request;

class CoachPhotoController extends Controller
{
    public function __construct(
        protected PhotoService $service
    ) {}

    /**
     * Obtener fotos de una evaluación
     */
    public function show(int $assessmentId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->show($assessmentId)
        ]);
    }

    /**
     * Guardar o actualizar fotos
     */
    public function store(Request $request, int $assessmentId)
    {
        return response()->json([
            'success' => true,
            'message' => 'Fotos guardadas correctamente.',
            'data' => $this->service->store($assessmentId, $request)
        ]);
    }

    /**
     * Eliminar una foto específica
     */
    public function destroy(int $assessmentId, string $type)
    {
        $this->service->destroy($assessmentId, $type);

        return response()->json([
            'success' => true,
            'message' => 'Foto eliminada correctamente.'
        ]);
    }
}