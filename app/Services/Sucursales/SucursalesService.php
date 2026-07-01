<?php

namespace App\Services\Sucursales;

use App\Models\Gym;
use App\Models\GymBranch;
use App\Models\GymBranchPayment;
use App\Models\GymBranchSubscription;
use App\Services\Clientes\ClienteService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SucursalesService
{
    public function __construct(private ClienteService $clienteService) {}

    // ── Queries ───────────────────────────────────────────────────────
    public function getBranches(array $filters, $user = null): array
    {
        $query = GymBranch::with([
            'gym.client.user',
            'subscriptions' => fn($q) => $q->orderByDesc('ends_at')
                ->with(['payments' => fn($p) => $p->orderByDesc('paid_at')]),
        ]);

        // ── Filtro por rol ────────────────────────────────────────────────
        if ($user) {
            $role = $user->modelHasRole?->role?->name;

            match ($role) {
                // Superadmin: ve todo
                'superadmin' => null,

                // Gym Owner: sucursales de sus gyms
                'gym_owner' => $query->whereHas('gym', fn($q) => $q->where('owner_id', $user->id)),

                // Admin, Coach, Client: solo la sucursal a la que están asignados
                'admin', 'coach', 'client' => $query->where('id', $user->gymbranch_id),

                default => abort(403, 'Rol no reconocido.')
            };
        }

        if (isset($filters['status'])) {
            match ($filters['status']) {
                'active'   => $query->where('is_active', 1),
                'inactive' => $query->where('is_active', 0),
                default    => null,
            };
        }

        if (!empty($filters['gym_id'])) {
            $query->where('gym_id', $filters['gym_id']);
        }

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(
                fn($q) => $q->where('gym_branches.name', 'LIKE', "%{$s}%")
                    ->orWhereHas('gym', fn($gq) => $gq->where('name', 'LIKE', "%{$s}%"))
            );
        }

        $branches  = $query->orderBy('gym_branches.created_at', 'desc')->get();
        $formatted = $branches->map(fn($b) => $this->formatBranch($b));

        if (!empty($filters['sub_status'])) {
            $formatted = $formatted->filter(function ($b) use ($filters) {
                $daysLeft = $b['days_left'];
                return match ($filters['sub_status']) {
                    'sub_active'   => $b['current_subscription'] && $daysLeft > 7,
                    'sub_expiring' => $b['current_subscription'] && $daysLeft <= 7 && $daysLeft > 0,
                    'sub_expired'  => $b['current_subscription'] && $daysLeft <= 0,
                    'no_sub'       => !$b['current_subscription'],
                    default        => true,
                };
            })->values();
        }

        return $formatted->values()->all();
    }

    public function getMetrics($user = null): array{
        $query = GymBranch::with(['subscriptions' => fn($q) => $q->orderByDesc('ends_at')]);

        // ── Filtro por rol ────────────────────────────────────────────────
        if ($user) {
            $role = $user->modelHasRole?->role?->name;

            match ($role) {
                'superadmin' => null,
                'gym_owner'  => $query->whereHas('gym', fn($q) => $q->where('owner_id', $user->id)),
                'admin', 'coach', 'client' => $query->where('id', $user->gymbranch_id),
                default => abort(403, 'Rol no reconocido.')
            };
        }

        $all = $query->get();

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

        return $metrics;
    }

    public function getBranchWithSubscriptions(int $id): array
    {
        $branch = GymBranch::with([
            'gym.client.user',
            'subscriptions' => fn($q) => $q->orderByDesc('starts_at')
                ->with(['payments' => fn($p) => $p->orderByDesc('paid_at')]),
        ])->findOrFail($id);

        $base = $this->formatBranch($branch);
        $base['subscriptions'] = $branch->subscriptions->map(fn($s) => $this->formatSubscription($s));

        return $base;
    }

    public function getGymsList(): Collection
    {
        return Gym::where('is_active', 1)->orderBy('name')->get(['id', 'name']);
    }

    // ── Mutations ─────────────────────────────────────────────────────

    public function createBranch(array $data): array
    {
        $branch = GymBranch::create($data);

        return $this->formatBranch(
            $branch->fresh(['gym.client', 'subscriptions.payments'])
        );
    }

    public function updateBranch(int $id, array $data): array
    {
        $branch = GymBranch::findOrFail($id);
        $branch->update($data);

        return $this->formatBranch(
            $branch->fresh(['gym.client', 'subscriptions.payments'])
        );
    }

    public function toggleBranch(int $id): array
    {
        $branch    = GymBranch::findOrFail($id);
        $newStatus = !$branch->is_active;
        $branch->update(['is_active' => $newStatus]);

        return ['is_active' => $newStatus];
    }

    public function deleteBranch(int $id): void
    {
        GymBranch::findOrFail($id)->delete();
    }

    public function createSubscription(int $branchId, array $data): array
    {
        $branch = GymBranch::findOrFail($branchId);

        $endsAt = !empty($data['ends_at'])
            ? $data['ends_at']
            : $this->calcEndsAt($data['starts_at'], $data['plan']);

        $branch->subscriptions()->update(['is_active' => 0]);

        $subscription = $branch->subscriptions()->create([
            'plan'      => $data['plan'],
            'price'     => $data['price'],
            'starts_at' => $data['starts_at'],
            'ends_at'   => $endsAt,
            'is_active' => $data['is_active'] ?? 1,
        ]);

        return $this->formatSubscription($subscription->fresh('payments'));
    }

    public function updateSubscription(int $branchId, int $subId, array $data): array
    {
        $subscription = GymBranchSubscription::where('gym_branch_id', $branchId)->findOrFail($subId);
        $subscription->update($data);

        return $this->formatSubscription($subscription->fresh('payments'));
    }

    public function createPayment(int $branchId, int $subId, array $data): array
    {
        $subscription = GymBranchSubscription::where('gym_branch_id', $branchId)->findOrFail($subId);

        $paidAt = !empty($data['paid_at'])
            ? $data['paid_at']
            : ($data['status'] === 'paid' ? now() : null);

        $payment = $subscription->payments()->create([
            'amount'         => $data['amount'],
            'status'         => $data['status'],
            'payment_method' => $data['payment_method'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'paid_at'        => $paidAt,
        ]);

        return $this->formatPayment($payment);
    }

    // ── Helpers privados ──────────────────────────────────────────────

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

    public function formatBranch(GymBranch $branch): array
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
                'client' => $branch->gym->client
                    ? $this->clienteService->formatClient(
                        $branch->gym->client->loadMissing(['user', 'gyms.branches'])
                    )
                    : null,
            ] : null,
            'current_subscription' => $sub ? $this->formatSubscription($sub) : null,
            'days_left'        => $daysLeft,
            'sub_status_label' => $subLabel,
            'sub_status_color' => $subColor,
            'last_payment'     => $lastPayment ? $this->formatPayment($lastPayment) : null,
            'created_at'       => $branch->created_at,
        ];
    }

    public function formatSubscription(GymBranchSubscription $sub): array
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

    public function formatPayment(GymBranchPayment $payment): array
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
}