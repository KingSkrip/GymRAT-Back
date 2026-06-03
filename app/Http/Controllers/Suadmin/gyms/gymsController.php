<?php

namespace App\Http\Controllers\Suadmin\gyms;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use Illuminate\Http\JsonResponse;

class GymsController extends Controller
{
    public function index(): JsonResponse
    {
        $gyms = Gym::with('branches')->get();

        $items = $gyms->map(function ($gym) {
            $branches = $gym->branches->map(fn($b) => [
                'id'        => $b->id,
                'name'      => $b->name,
                'address'   => $b->address,
                'phone'     => $b->phone,
                'is_active' => $b->is_active,
            ]);

            return [
                'id'         => $gym->id,
                'name'       => $gym->name,
                'address'    => $gym->address,
                'phone'      => $gym->phone,
                'sub'        => $gym->branches->count() . ' sucursal(es) · ' . $gym->address,
                'badge'      => $gym->is_active ? 'Activo' : 'Inactivo',
                'badgeColor' => $gym->is_active ? 'green' : 'red',
                'branches'   => $branches,
            ];
        });

        return response()->json(['total' => $gyms->count(), 'items' => $items]);
    }


    public function storeBranch(Request $request, Gym $gym): JsonResponse
{
    $branch = $gym->branches()->create($request->only(['name','address','phone']));
    return response()->json($branch, 201);
}

public function updateBranch(Request $request, Gym $gym, $branchId): JsonResponse
{
    $branch = $gym->branches()->findOrFail($branchId);
    $branch->update($request->only(['name','address','phone','is_active']));
    return response()->json($branch);
}
}