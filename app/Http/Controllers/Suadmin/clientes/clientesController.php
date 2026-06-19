<?php

namespace App\Http\Controllers\Suadmin\Clientes;

use App\Http\Controllers\Controller;
use App\Models\SystemClient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientesController extends Controller
{
    // ── GET /suadmin/clientes ─────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = SystemClient::with(['gyms.branches', 'subscriptions']);

        // Filtro por estado
        if ($request->has('status')) {
            match ($request->status) {
                'active'   => $query->where('is_active', 1),
                'inactive' => $query->where('is_active', 0),
                'expiring' => $query->where('is_active', 1)->whereBetween(
                    'subscription_end',
                    [Carbon::today(), Carbon::today()->addDays(7)]
                ),
                'expired'  => $query->where(function ($q) {
                    $q->whereDate('subscription_end', '<', Carbon::today())
                        ->orWhereNull('subscription_end');
                }),
                default => null,
            };
        }

        // Búsqueda por nombre o email
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'LIKE', "%{$s}%")
                ->orWhere('email', 'LIKE', "%{$s}%"));
        }

        $clients = $query->orderBy('created_at', 'desc')->get();

        // ── Métricas globales ────────────────────────────────────────
        $all = SystemClient::with(['gyms.branches'])->get();

        $metrics = [
            'total'      => $all->count(),
            'active'     => $all->where('is_active', 1)->count(),
            'inactive'   => $all->where('is_active', 0)->count(),
            'expiring'   => $all->filter(fn($c) => $this->daysLeft($c) <= 7 && $this->daysLeft($c) > 0)->count(),
            'expired'    => $all->filter(fn($c) => $this->daysLeft($c) <= 0 && $c->subscription_end)->count(),
            'total_gyms' => $all->sum(fn($c) => $c->gyms->count()),
            'total_branches' => $all->sum(fn($c) => $c->gyms->sum(fn($g) => $g->branches->count())),
        ];

        return response()->json([
            'metrics' => $metrics,
            'data'    => $clients->map(fn($c) => $this->formatClient($c)),
        ]);
    }

    // ── GET /suadmin/clientes/{id} ────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $client = SystemClient::with(['gyms.branches', 'subscriptions'])->findOrFail($id);

        return response()->json([
            'data' => $this->formatClientDetail($client),
        ]);
    }

    // ── POST /suadmin/clientes ────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:255',
            'email'              => 'required|email|unique:system_clients,email',
            'phone'              => 'nullable|string|max:20',
            'subscription_start' => 'required|date',
            'subscription_end'   => 'required|date|after:subscription_start',
            'is_active'          => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client = SystemClient::create([
            'name'               => $request->name,
            'email'              => $request->email,
            'phone'              => $request->phone,
            'subscription_start' => $request->subscription_start,
            'subscription_end'   => $request->subscription_end,
            'is_active'          => $request->input('is_active', 1),
        ]);

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'data'    => $this->formatClientDetail($client->fresh(['gyms.branches', 'subscriptions'])),
        ], 201);
    }

    // ── PUT /suadmin/clientes/{id} ────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $client = SystemClient::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'               => 'sometimes|required|string|max:255',
            'email'              => "sometimes|required|email|unique:system_clients,email,{$id}",
            'phone'              => 'nullable|string|max:20',
            'subscription_start' => 'sometimes|required|date',
            'subscription_end'   => 'sometimes|required|date',
            'is_active'          => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client->update($request->only([
            'name',
            'email',
            'phone',
            'subscription_start',
            'subscription_end',
            'is_active',
        ]));

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'data'    => $this->formatClientDetail($client->fresh(['gyms.branches', 'subscriptions'])),
        ]);
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
        $client = SystemClient::findOrFail($id);
        $client->delete(); // cascade en BD elimina gyms → branches → usuarios

        return response()->json(['message' => 'Cliente eliminado correctamente.']);
    }

    // ── Helpers ──────────────────────────────────────────────────────
    private function formatClient(SystemClient $client): array
    {
        $daysLeft    = $this->daysLeft($client);
        $gymCount    = $client->gyms->count();
        $branchCount = $client->gyms->sum(fn($g) => $g->branches->count());

        [$statusLabel, $statusColor] = $this->resolveStatus($client, $daysLeft);

        return [
            'id'               => $client->id,
            'name'             => $client->name,
            'email'            => $client->email,
            'phone'            => $client->phone,
            'is_active'        => (bool) $client->is_active,
            'subscription_start' => $client->subscription_start,
            'subscription_end'   => $client->subscription_end,
            'days_left'        => $daysLeft,
            'gym_count'        => $gymCount,
            'branch_count'     => $branchCount,
            'status_label'     => $statusLabel,
            'status_color'     => $statusColor, // 'green' | 'yellow' | 'red' | 'gray'
            'created_at'       => $client->created_at,
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