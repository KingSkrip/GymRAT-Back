<?php

namespace App\Http\Controllers\Suadmin\Clientes;

use App\Http\Controllers\Controller;
use App\Models\ModelHasRole;
use App\Models\SystemClient;
use App\Models\User;
use Carbon\Carbon;
use Exception as GlobalException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClientesController extends Controller
{
    // ── GET /suadmin/clientes ─────────────────────────────────────────
    public function index()
    {
        $clients = SystemClient::query()
            ->with(['user:id,name,email,phone'])
            ->withCount([
                'gyms',
                'gyms as branch_count' => function ($query) {
                    $query->join('gym_branches', 'gym_branches.gym_id', '=', 'gyms.id');
                }
            ])
            ->get()
            ->map(function ($client) {

                return [
                    'id' => $client->id,

                    // ✔ viene del USER
                    'name' => $client->user?->name,
                    'email' => $client->user?->email,
                    'phone' => $client->user?->phone,

                    'is_active' => $client->is_active,
                    'subscription_start' => $client->subscription_start,
                    'subscription_end' => $client->subscription_end,

                    'days_left' => $client->subscription_end?->diffInDays($client->subscription_end, false),

                    'gym_count' => $client->gyms_count ?? 0,
                    'branch_count' => $client->branch_count ?? 0,

                    'status_label' => $client->is_active ? 'Activo' : 'Inactivo',
                    'status_color' => $client->is_active ? 'green' : 'red',

                    'created_at' => $client->created_at,
                ];
            });

        return response()->json([
            'metrics' => [
                'total' => $clients->count(),
                'active' => $clients->where('is_active', true)->count(),
                'inactive' => $clients->where('is_active', false)->count(),
            ],
            'data' => $clients
        ]);
    }
    // ── GET /suadmin/clientes/{id} ────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $client = SystemClient::with([
            'user:id,name,email,phone',
            'gyms.branches',
            'gyms.owner:id,name',
            'subscriptions'
        ])->findOrFail($id);

        return response()->json([
            'data' => $this->formatClientDetail($client),
        ]);
    }

    // ── POST /suadmin/clientes ────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            // 'subscription_start' => 'required|date',
            // 'subscription_end'   => 'required|date|after:subscription_start',

            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {

            // 🔥 1. USER
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'phone'     => $request->phone,
                'password'  => Hash::make($request->password),
                'is_active' => $request->input('is_active', true),
            ]);

            // 🔥 2. ROLE (OWNER = 2)
            ModelHasRole::create([
                'role_id'     => 2,
                'sub_role_id' => null,
                'model_type'  => User::class,
                'model_id'    => $user->id,
            ]);

            // 🔥 3. SYSTEM CLIENT
            $client = SystemClient::create([
                'user_id'            => $user->id,
                'is_active'          => $request->input('is_active', true),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Cliente creado correctamente.',
                'data' => $this->formatClientDetail(
                    $client->load(['user', 'gyms.branches', 'subscriptions'])
                ),
            ], 201);
        } catch (GlobalException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ── PUT /suadmin/clientes/{id} ────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $client = SystemClient::with('user')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'               => 'sometimes|required|string|max:255',
            'email'              => "sometimes|required|email|unique:users,email," . $client->user_id,
            'phone'              => 'nullable|string|max:20',

            // 'subscription_start' => 'sometimes|required|date',
            // 'subscription_end'   => 'sometimes|required|date|after:subscription_start',

            'is_active'          => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {

            // 🔥 1. ACTUALIZAR USER
            if ($client->user) {
                $client->user->update([
                    'name'  => $request->name ?? $client->user->name,
                    'email' => $request->email ?? $client->user->email,
                    'phone' => $request->phone ?? $client->user->phone,
                ]);
            }

            // 🔥 2. ACTUALIZAR SYSTEM CLIENT
            $client->update([
                // 'subscription_start' => $request->subscription_start ?? $client->subscription_start,
                // 'subscription_end'   => $request->subscription_end ?? $client->subscription_end,
                'is_active'          => $request->has('is_active')
                    ? $request->is_active
                    : $client->is_active,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Cliente actualizado correctamente.',
                'data' => $this->formatClientDetail(
                    $client->fresh(['user', 'gyms.branches', 'subscriptions'])
                ),
            ]);
        } catch (GlobalException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ── PATCH /suadmin/clientes/{id}/toggle ──────────────────────────
    // Activa o desactiva el cliente (y en cascada todos sus gyms/sucursales)
    public function toggle(int $id): JsonResponse
    {
        $client = SystemClient::with('gyms.branches')->findOrFail($id);

        $newStatus = !$client->is_active;

        // Desactivar/activar en cascada
        $client->update(['is_active' => $newStatus]);

        foreach ($client->gyms as $gym) {
            $gym->update(['is_active' => $newStatus]);
            foreach ($gym->branches as $branch) {
                $branch->update(['is_active' => $newStatus]);
            }
        }

        $action = $newStatus ? 'activado' : 'desactivado';

        return response()->json([
            'message'   => "Cliente {$action} correctamente. Todos sus gyms y sucursales fueron {$action}s.",
            'is_active' => $newStatus,
        ]);
    }

    // ── DELETE /suadmin/clientes/{id} ─────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $client = SystemClient::with('user')->findOrFail($id);
        DB::beginTransaction();
        try {
            $user = $client->user;
            $client->delete();
            if ($user) {
                ModelHasRole::where('model_type', User::class)
                    ->where('model_id', $user->id)
                    ->delete();
                $user->delete();
            }

            DB::commit();

            return response()->json(['message' => 'Cliente eliminado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al eliminar cliente',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    // ── Helpers ──────────────────────────────────────────────────────
    private function formatClient(SystemClient $client): array
    {
        $daysLeft = $this->daysLeft($client);
        $gymCount = $client->gyms->count();
        $branchCount = $client->gyms->sum(fn($g) => $g->branches->count());

        [$statusLabel, $statusColor] = $this->resolveStatus($client, $daysLeft);

        return [
            'id' => $client->id,

            // 🔥 AQUÍ ESTÁ EL FIX REAL
            'name'  => $client->user->name ?? null,
            'email' => $client->user->email ?? null,
            'phone' => $client->user->phone ?? null,

            'is_active' => (bool) $client->is_active,
            'subscription_start' => $client->subscription_start,
            'subscription_end' => $client->subscription_end,

            'days_left' => $daysLeft,
            'gym_count' => $gymCount,
            'branch_count' => $branchCount,

            'status_label' => $statusLabel,
            'status_color' => $statusColor,

            'created_at' => $client->created_at,
        ];
    }

    private function formatClientDetail(SystemClient $client): array
    {
        $base = $this->formatClient($client);

        $base['gyms'] = $client->gyms->map(fn($gym) => [
            'id'           => $gym->id,
            'name'         => $gym->name,
            'address'      => $gym->address,
            'phone'        => $gym->phone,
            'is_active'    => (bool) $gym->is_active,
            'branch_count' => $gym->branches->count(),
            'branches'     => $gym->branches->map(fn($b) => [
                'id'        => $b->id,
                'name'      => $b->name,
                'address'   => $b->address,
                'is_active' => (bool) $b->is_active,
            ]),
        ]);

        return $base;
    }

    private function daysLeft(SystemClient $client): int
    {
        if (!$client->subscription_end) return -1;
        return (int) Carbon::today()->diffInDays($client->subscription_end, false);
    }

    private function resolveStatus(SystemClient $client, int $daysLeft): array
    {
        if (!$client->is_active)    return ['Inactivo',        'gray'];
        if ($daysLeft <= 0)         return ['Vencido',         'red'];
        if ($daysLeft <= 3)         return ["Vence en {$daysLeft}d", 'red'];
        if ($daysLeft <= 7)         return ["Vence en {$daysLeft}d", 'yellow'];
        return ['Activo',           'green'];
    }
}