<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\DietUser;
use App\Services\Diet\DietService;
use Illuminate\Http\Request;

class CoachDietController extends Controller
{
    protected DietService $service;

    public function __construct(DietService $service)
    {
        $this->service = $service;
    }

    /**
     * Listar dietas
     */
    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->index($request)
        ]);
    }

    /**
     * Crear dieta
     */
    public function store(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Dieta creada correctamente.',
            'data' => $this->service->store($request->all())
        ], 201);
    }

    /**
     * Mostrar dieta
     */
    public function show(int $id)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->show($id)
        ]);
    }

    /**
     * Actualizar dieta
     */
    public function update(Request $request, DietUser $diet)
    {
        return response()->json([
            'success' => true,
            'message' => 'Dieta actualizada correctamente.',
            'data' => $this->service->update($diet, $request->all())
        ]);
    }

    /**
     * Eliminar dieta
     */
    public function destroy(DietUser $diet)
    {
        $this->service->destroy($diet);

        return response()->json([
            'success' => true,
            'message' => 'Dieta eliminada correctamente.'
        ]);
    }

    /**
     * Activar / Desactivar dieta
     */
    public function toggleActive(DietUser $diet)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->toggleActive($diet)
        ]);
    }

    /**
     * Duplicar dieta
     */
    public function duplicate(DietUser $diet)
    {
        return response()->json([
            'success' => true,
            'message' => 'Dieta duplicada correctamente.',
            'data' => $this->service->duplicate($diet)
        ]);
    }

    /**
     * Obtener dieta activa de un cliente
     */
    public function active(int $userId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->activeDiet($userId)
        ]);
    }
}
