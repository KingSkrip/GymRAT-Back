<?php

namespace App\Http\Controllers\Suadmin\Gyms;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\GymBranch;
use App\Models\SystemClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GymsController extends Controller
{
    // ── GET /gestion/gyms ────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = Gym::with(['branches', 'client']);

        // Filtro por estado
        if ($request->has('status')) {
            match ($request->status) {
                'active'   => $query->where('is_active', 1),
                'inactive' => $query->where('is_active', 0),
                default    => null,
            };
        }

        // Filtro por cliente
        if ($request->filled('client_id')) {
            $query->where('system_client_id', $request->client_id);
        }

        // Búsqueda por nombre, dirección o teléfono
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(
                fn($q) =>
                $q->where('name', 'LIKE', "%{$s}%")
                    ->orWhere('address', 'LIKE', "%{$s}%")
                    ->orWhere('phone', 'LIKE', "%{$s}%")
                    ->orWhereHas(
                        'client',
                        fn($cq) =>
                        $cq->where('name', 'LIKE', "%{$s}%")
                    )
            );
        }

        $gyms = $query->orderBy('created_at', 'desc')->get();

        // ── Métricas globales ─────────────────────────────────────────
        $all = Gym::with('branches')->get();

        $metrics = [
            'total'          => $all->count(),
            'active'         => $all->where('is_active', 1)->count(),
            'inactive'       => $all->where('is_active', 0)->count(),
            'total_branches' => $all->sum(fn($g) => $g->branches->count()),
        ];

        return response()->json([
            'metrics' => $metrics,
            'data'    => $gyms->map(fn($g) => $this->formatGym($g)),
        ]);
    }

    // ── GET /gestion/gyms/{id} ───────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $gym = Gym::with(['branches', 'client'])->findOrFail($id);

        return response()->json([
            'data' => $this->formatGymDetail($gym),
        ]);
    }

    // ── POST /gestion/gyms ───────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'system_client_id' => 'required|exists:system_clients,id',
            'address'          => 'nullable|string|max:500',
            'phone'            => 'nullable|string|max:20',
            'is_active'        => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $gym = Gym::create([
            'name'             => $request->name,
            'system_client_id' => $request->system_client_id,
            'address'          => $request->address,
            'phone'            => $request->phone,
            'is_active'        => $request->input('is_active', 1),
        ]);

        return response()->json([
            'message' => 'Gym creado correctamente.',
            'data'    => $this->formatGymDetail($gym->fresh(['branches', 'client'])),
        ], 201);
    }

    // ── PUT /gestion/gyms/{id} ───────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $gym = Gym::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'             => 'sometimes|required|string|max:255',
            'system_client_id' => 'sometimes|required|exists:system_clients,id',
            'address'          => 'nullable|string|max:500',
            'phone'            => 'nullable|string|max:20',
            'is_active'        => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $gym->update($request->only([
            'name',
            'system_client_id',
            'address',
            'phone',
            'is_active',
        ]));

        return response()->json([
            'message' => 'Gym actualizado correctamente.',
            'data'    => $this->formatGymDetail($gym->fresh(['branches', 'client'])),
        ]);
    }

    // ── PATCH /gestion/gyms/{id}/toggle ─────────────────────────────
    public function toggle(int $id): JsonResponse
    {
        $gym = Gym::with('branches')->findOrFail($id);

        $newStatus = !$gym->is_active;

        $gym->update(['is_active' => $newStatus]);

        // Cascada a sucursales
        foreach ($gym->branches as $branch) {
            $branch->update(['is_active' => $newStatus]);
        }

        $action = $newStatus ? 'activado' : 'desactivado';

        return response()->json([
            'message'   => "Gym {$action} correctamente. Todas sus sucursales fueron {$action}s.",
            'is_active' => $newStatus,
        ]);
    }

    // ── DELETE /gestion/gyms/{id} ────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $gym = Gym::findOrFail($id);
        $gym->delete(); // cascade en BD elimina branches y usuarios

        return response()->json(['message' => 'Gym eliminado correctamente.']);
    }

    // ── POST /gestion/gyms/{gym}/branches ────────────────────────────
    public function storeBranch(Request $request, int $gymId): JsonResponse
    {
        $gym = Gym::findOrFail($gymId);

        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'address'   => 'nullable|string|max:500',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch = $gym->branches()->create([
            'name'      => $request->name,
            'address'   => $request->address,
            'phone'     => $request->phone,
            'is_active' => $request->input('is_active', 1),
        ]);

        return response()->json($this->formatBranch($branch), 201);
    }

    // ── PUT /gestion/gyms/{gym}/branches/{branch} ────────────────────
    public function updateBranch(Request $request, int $gymId, int $branchId): JsonResponse
    {
        $branch = GymBranch::where('gym_id', $gymId)->findOrFail($branchId);

        $validator = Validator::make($request->all(), [
            'name'      => 'sometimes|required|string|max:255',
            'address'   => 'nullable|string|max:500',
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch->update($request->only(['name', 'address', 'phone', 'is_active']));

        return response()->json($this->formatBranch($branch->fresh()));
    }

    // ── DELETE /gestion/gyms/{gym}/branches/{branch} ─────────────────
    public function destroyBranch(int $gymId, int $branchId): JsonResponse
    {
        $branch = GymBranch::where('gym_id', $gymId)->findOrFail($branchId);
        $branch->delete();

        return response()->json(['message' => 'Sucursal eliminada correctamente.']);
    }

    // ── GET /gestion/clients-list ────────────────────────────────────
    public function clientsList(Request $request): JsonResponse
    {
        $query = SystemClient::select('id', 'name', 'email');

        if ($request->filled('include_id')) {
            $query->where(
                fn($q) =>
                $q->where('is_active', 1)
                    ->orWhere('id', $request->include_id)
            );
        } else {
            $query->where('is_active', 1);
        }

        $clients = $query->orderBy('name')->get();
        return response()->json(['data' => $clients]);
    }
    // ── Helpers ──────────────────────────────────────────────────────
    private function formatGym(Gym $gym): array
    {
        return [
            'id'               => $gym->id,
            'name'             => $gym->name,
            'address'          => $gym->address,
            'phone'            => $gym->phone,
            'is_active'        => (bool) $gym->is_active,
            'branch_count'     => $gym->branches->count(),
            'status_label'     => $gym->is_active ? 'Activo' : 'Inactivo',
            'status_color'     => $gym->is_active ? 'green' : 'gray',
            'created_at'       => $gym->created_at,
            'system_client_id' => $gym->system_client_id,
            'client'           => $gym->client ? [
                'id'    => $gym->client->id,
                'name'  => $gym->client->name,
                'email' => $gym->client->email,
            ] : null,
        ];
    }

    private function formatGymDetail(Gym $gym): array
    {
        $base = $this->formatGym($gym);

        $base['branches'] = $gym->branches->map(fn($b) => $this->formatBranch($b));

        return $base;
    }

    private function formatBranch(GymBranch $branch): array
    {
        return [
            'id'        => $branch->id,
            'name'      => $branch->name,
            'address'   => $branch->address,
            'phone'     => $branch->phone,
            'is_active' => (bool) $branch->is_active,
        ];
    }
}