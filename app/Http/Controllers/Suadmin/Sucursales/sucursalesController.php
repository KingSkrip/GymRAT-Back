<?php

namespace App\Http\Controllers\Suadmin\Sucursales;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\GymBranch;
use App\Models\GymBranchPayment;
use App\Models\GymBranchSubscription;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SucursalesController extends Controller
{
    // ── GET /gestion/sucursales ───────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $query = GymBranch::with([
            'gym.client',
            'subscriptions' => fn($q) => $q->orderByDesc('ends_at')
                ->with(['payments' => fn($p) => $p->orderByDesc('paid_at')]),
        ]);

        // Filtro por estado de la sucursal
        if ($request->has('status')) {
            match ($request->status) {
                'active'   => $query->where('is_active', 1),
                'inactive' => $query->where('is_active', 0),
                default    => null,
            };
        }

        // Filtro por gym
        if ($request->filled('gym_id')) {
            $query->where('gym_id', $request->gym_id);
        }

        // Búsqueda por nombre/dirección de sucursal o nombre del gym/cliente
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(
                fn($q) => $q->where('name', 'LIKE', "%{$s}%")
                    ->orWhere('address', 'LIKE', "%{$s}%")
                    ->orWhereHas(
                        'gym',
                        fn($gq) => $gq->where('name', 'LIKE', "%{$s}%")
                            ->orWhereHas('client', fn($cq) => $cq->where('name', 'LIKE', "%{$s}%"))
                    )
            );
        }

        $branches = $query->orderBy('created_at', 'desc')->get();

        $formatted = $branches->map(fn($b) => $this->formatBranch($b));

        // Filtro por estado de suscripción (calculado)
        if ($request->filled('sub_status')) {
            $formatted = $formatted->filter(function ($b) use ($request) {
                $daysLeft = $b['days_left'];
                return match ($request->sub_status) {
                    'sub_active'   => $b['current_subscription'] && $daysLeft > 7,
                    'sub_expiring' => $b['current_subscription'] && $daysLeft <= 7 && $daysLeft > 0,
                    'sub_expired'  => $b['current_subscription'] && $daysLeft <= 0,
                    'no_sub'       => !$b['current_subscription'],
                    default        => true,
                };
            })->values();
        }

        // ── Métricas globales ──────────────────────────────────────────
        $all = GymBranch::with(['subscriptions' => fn($q) => $q->orderByDesc('ends_at')])->get();

        $metrics = [
            'total'        => $all->count(),
            'active'       => $all->where('is_active', 1)->count(),
            'inactive'     => $all->where('is_active', 0)->count(),
            'sub_active'   => 0,
            'sub_expiring' => 0,
            'sub_expired'  => 0,
            'no_sub'       => 0,
        ];

        foreach ($all as $branch) {
            $sub = $this->currentSubscription($branch);

            if (!$sub) {
                $metrics['no_sub']++;
                continue;
            }

            $daysLeft = $this->daysLeftFor($sub);

            if ($daysLeft <= 0) {
                $metrics['sub_expired']++;
            } elseif ($daysLeft <= 7) {
                $metrics['sub_expiring']++;
            } else {
                $metrics['sub_active']++;
            }
        }

        return response()->json([
            'metrics' => $metrics,
            'data'    => $formatted->values(),
        ]);
    }

    // ── GET /gestion/sucursales/{id} ──────────────────────────────────
    public function show(int $id): JsonResponse
    {
        $branch = GymBranch::with([
            'gym.client',
            'subscriptions' => fn($q) => $q->orderByDesc('starts_at')
                ->with(['payments' => fn($p) => $p->orderByDesc('paid_at')]),
        ])->findOrFail($id);

        $base = $this->formatBranch($branch);
        $base['subscriptions'] = $branch->subscriptions->map(fn($s) => $this->formatSubscription($s));

        return response()->json(['data' => $base]);
    }

    // ── PUT /gestion/sucursales/{id} ──────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $branch = GymBranch::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'      => 'sometimes|required|string|max:255',
            'address'   => 'nullable|string|max:500',
            'phone'     => 'nullable|string|max:20',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch->update($request->only([
            'name',
            'address',
            'phone',
            'latitude',
            'longitude',
            'is_active',
        ]));

        return response()->json([
            'message' => 'Sucursal actualizada correctamente.',
            'data'    => $this->formatBranch($branch->fresh([
                'gym.client',
                'subscriptions.payments',
            ])),
        ]);
    }

    // ── PATCH /gestion/sucursales/{id}/toggle ────────────────────────
    public function toggle(int $id): JsonResponse
    {
        $branch = GymBranch::findOrFail($id);

        $newStatus = !$branch->is_active;
        $branch->update(['is_active' => $newStatus]);

        $action = $newStatus ? 'activada' : 'desactivada';

        return response()->json([
            'message'   => "Sucursal {$action} correctamente.",
            'is_active' => $newStatus,
        ]);
    }

    // ── DELETE /gestion/sucursales/{id} ──────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $branch = GymBranch::findOrFail($id);
        $branch->delete(); // cascade elimina subscriptions → payments

        return response()->json(['message' => 'Sucursal eliminada correctamente.']);
    }

    // ── POST /gestion/sucursales/{id}/subscriptions ──────────────────
    // Crea una nueva suscripción (renovación) y desactiva las anteriores
    public function storeSubscription(Request $request, int $id): JsonResponse
    {
        $branch = GymBranch::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'plan'      => 'required|in:monthly,quarterly,yearly',
            'price'     => 'required|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at'   => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $endsAt = $request->filled('ends_at')
            ? $request->ends_at
            : $this->calcEndsAt($request->starts_at, $request->plan);

        // Cualquier suscripción previa pasa a inactiva
        $branch->subscriptions()->update(['is_active' => 0]);

        $subscription = $branch->subscriptions()->create([
            'plan'      => $request->plan,
            'price'     => $request->price,
            'starts_at' => $request->starts_at,
            'ends_at'   => $endsAt,
            'is_active' => $request->input('is_active', 1),
        ]);

        return response()->json([
            'message' => 'Suscripción registrada correctamente.',
            'data'    => $this->formatSubscription($subscription->fresh('payments')),
        ], 201);
    }

    // ── PUT /gestion/sucursales/{id}/subscriptions/{subId} ───────────
    public function updateSubscription(Request $request, int $id, int $subId): JsonResponse
    {
        $subscription = GymBranchSubscription::where('gym_branch_id', $id)->findOrFail($subId);

        $validator = Validator::make($request->all(), [
            'plan'      => 'sometimes|required|in:monthly,quarterly,yearly',
            'price'     => 'sometimes|required|numeric|min:0',
            'starts_at' => 'sometimes|required|date',
            'ends_at'   => 'sometimes|required|date',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscription->update($request->only([
            'plan',
            'price',
            'starts_at',
            'ends_at',
            'is_active',
        ]));

        return response()->json([
            'message' => 'Suscripción actualizada correctamente.',
            'data'    => $this->formatSubscription($subscription->fresh('payments')),
        ]);
    }

    // ── POST /gestion/sucursales/{id}/subscriptions/{subId}/payments ─
    public function storePayment(Request $request, int $id, int $subId): JsonResponse
    {
        $subscription = GymBranchSubscription::where('gym_branch_id', $id)->findOrFail($subId);

        $validator = Validator::make($request->all(), [
            'amount'         => 'required|numeric|min:0',
            'status'         => 'required|in:pending,paid,failed,cancelled',
            'payment_method' => 'nullable|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'paid_at'        => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paidAt = $request->filled('paid_at')
            ? $request->paid_at
            : ($request->status === 'paid' ? now() : null);

        $payment = $subscription->payments()->create([
            'amount'         => $request->amount,
            'status'         => $request->status,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'paid_at'        => $paidAt,
        ]);

        return response()->json([
            'message' => 'Pago registrado correctamente.',
            'data'    => $this->formatPayment($payment),
        ], 201);
    }

    // ── GET /gestion/gyms-list ─────────────────────────────────────────
    // Lista simple de gyms activos para el filtro/select
    public function gymsList(Request $request): JsonResponse
    {
        $gyms = Gym::where('is_active', 1)->orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => $gyms]);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Suscripción "actual" de una sucursal: la marcada como activa,
     * o en su defecto la de fecha de fin más reciente.
     */
    private function currentSubscription(GymBranch $branch): ?GymBranchSubscription
    {
        return $branch->subscriptions->firstWhere('is_active', 1)
            ?? $branch->subscriptions->sortByDesc('ends_at')->first();
    }

    private function daysLeftFor(?GymBranchSubscription $sub): ?int
    {
        if (!$sub) return null;
        return (int) Carbon::today()->diffInDays($sub->ends_at, false);
    }

    private function calcEndsAt(string $startsAt, string $plan): string
    {
        $start = Carbon::parse($startsAt);

        return match ($plan) {
            'monthly'   => $start->copy()->addMonth()->toDateString(),
            'quarterly' => $start->copy()->addMonths(3)->toDateString(),
            'yearly'    => $start->copy()->addYear()->toDateString(),
            default     => $start->copy()->addMonth()->toDateString(),
        };
    }

    private function resolveSubStatus(?GymBranchSubscription $sub, ?int $daysLeft): array
    {
        if (!$sub) return ['Sin suscripción', 'gray'];
        if ($daysLeft <= 0) return ['Vencida', 'red'];
        if ($daysLeft <= 3) return ["Vence en {$daysLeft}d", 'red'];
        if ($daysLeft <= 7) return ["Vence en {$daysLeft}d", 'yellow'];
        return ['Activa', 'green'];
    }

    private function formatBranch(GymBranch $branch): array
    {
        $sub      = $this->currentSubscription($branch);
        $daysLeft = $this->daysLeftFor($sub);
        [$subLabel, $subColor] = $this->resolveSubStatus($sub, $daysLeft);

        $lastPayment = $sub?->payments?->first();

        return [
            'id'        => $branch->id,
            'gym_id'    => $branch->gym_id,
            'name'      => $branch->name,
            'address'   => $branch->address,
            'phone'     => $branch->phone,
            'latitude'  => $branch->latitude,
            'longitude' => $branch->longitude,
            'is_active' => (bool) $branch->is_active,
            'status_label' => $branch->is_active ? 'Activa' : 'Inactiva',
            'status_color' => $branch->is_active ? 'green' : 'gray',
            'gym' => $branch->gym ? [
                'id'     => $branch->gym->id,
                'name'   => $branch->gym->name,
                'client' => $branch->gym->client ? [
                    'id'   => $branch->gym->client->id,
                    'name' => $branch->gym->client->name,
                ] : null,
            ] : null,
            'current_subscription' => $sub ? $this->formatSubscription($sub) : null,
            'days_left'        => $daysLeft,
            'sub_status_label' => $subLabel,
            'sub_status_color' => $subColor,
            'last_payment'     => $lastPayment ? $this->formatPayment($lastPayment) : null,
            'created_at'       => $branch->created_at,
        ];
    }

    private function formatSubscription(GymBranchSubscription $sub): array
    {
        return [
            'id'            => $sub->id,
            'gym_branch_id' => $sub->gym_branch_id,
            'plan'          => $sub->plan,
            'price'         => $sub->price,
            'starts_at'     => $sub->starts_at,
            'ends_at'       => $sub->ends_at,
            'is_active'     => (bool) $sub->is_active,
            'payments'      => $sub->payments?->map(fn($p) => $this->formatPayment($p))->values() ?? [],
        ];
    }

    private function formatPayment(GymBranchPayment $payment): array
    {
        return [
            'id'             => $payment->id,
            'amount'         => $payment->amount,
            'status'         => $payment->status,
            'payment_method' => $payment->payment_method,
            'transaction_id' => $payment->transaction_id,
            'paid_at'        => $payment->paid_at,
        ];
    }

    // SucursalesController.php

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'gym_id'    => 'required|exists:gyms,id',
            'address'   => 'nullable|string|max:500',
            'phone'     => 'nullable|string|max:20',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch = GymBranch::create($request->only([
            'gym_id',
            'name',
            'address',
            'phone',
            'latitude',
            'longitude',
            'is_active',
        ]));

        return response()->json([
            'message' => 'Sucursal creada correctamente.',
            'data'    => $this->formatBranch($branch->fresh(['gym.client', 'subscriptions.payments'])),
        ], 201);
    }
}