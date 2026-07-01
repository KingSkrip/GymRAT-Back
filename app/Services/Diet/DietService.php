<?php

namespace App\Services\Diet;

use App\Models\DietUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DietService
{
    /**
     * Listar dietas
     */
    public function index(Request $request)
    {
        $query = DietUser::with([
            'coach',
            'client',
            'meals'
        ]);

        if ($request->filled('coach_id')) {
            $query->where('coach_id', $request->coach_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)
            );
        }

        return $query
            ->latest()
            ->paginate($request->get('per_page', 10));
    }

    /**
     * Crear dieta
     */
    public function store(array $data): DietUser
    {
        return DB::transaction(function () use ($data) {

            $meals = $data['meals'] ?? [];

            unset($data['meals']);

            $diet = DietUser::create($data);

            foreach ($meals as $meal) {

                $diet->meals()->create($meal);
            }

            return $diet->load([
                'coach',
                'client',
                'meals'
            ]);
        });
    }

    /**
     * Mostrar dieta
     */
    public function show(int $id): DietUser
    {
        return DietUser::with([
            'coach',
            'client',
            'meals'
        ])->findOrFail($id);
    }

    /**
     * Actualizar dieta
     */
    public function update(DietUser $diet, array $data): DietUser
    {
        return DB::transaction(function () use ($diet, $data) {

            $meals = $data['meals'] ?? [];

            unset($data['meals']);

            $diet->update($data);

            if (!empty($meals)) {

                $diet->meals()->delete();

                foreach ($meals as $meal) {

                    $diet->meals()->create($meal);
                }
            }

            return $diet->load([
                'coach',
                'client',
                'meals'
            ]);
        });
    }

    /**
     * Eliminar dieta
     */
    public function destroy(DietUser $diet): void
    {
        DB::transaction(function () use ($diet) {

            $diet->delete();
        });
    }

    /**
     * Activar / Desactivar dieta
     */
    public function toggleActive(DietUser $diet): DietUser
    {
        $diet->update([
            'is_active' => !$diet->is_active
        ]);

        return $diet->fresh([
            'coach',
            'client',
            'meals'
        ]);
    }

    /**
     * Duplicar dieta
     */
    public function duplicate(DietUser $diet): DietUser
    {
        return DB::transaction(function () use ($diet) {

            $newDiet = $diet->replicate();

            $newDiet->title .= ' (Copia)';
            $newDiet->is_active = false;

            $newDiet->save();

            foreach ($diet->meals as $meal) {

                $newDiet->meals()->create([
                    'meal' => $meal->meal,
                    'food' => $meal->food,
                    'quantity' => $meal->quantity,
                    'unit' => $meal->unit,
                    'order' => $meal->order,
                ]);
            }

            return $newDiet->load([
                'coach',
                'client',
                'meals'
            ]);
        });
    }

    /**
     * Dieta activa del cliente
     */
    public function activeDiet(int $userId): ?DietUser
    {
        return DietUser::with([
            'coach',
            'client',
            'meals'
        ])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }
}
