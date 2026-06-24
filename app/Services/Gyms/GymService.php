<?php

namespace App\Services\Gyms;

use App\Models\Gym;
use App\Models\GymBranch;
use App\Models\SystemClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GymService
{
    // public function getGyms(Request $request): array
    // {
    //     $query = Gym::with(['branches', 'client']);

    //     if ($request->has('status')) {
    //         match ($request->status) {
    //             'active' => $query->where('is_active', 1),
    //             'inactive' => $query->where('is_active', 0),
    //             default => null,
    //         };
    //     }

    //     if ($request->filled('client_id')) {
    //         $query->where('system_client_id', $request->client_id);
    //     }

    //     if ($request->filled('search')) {
    //         $s = $request->search;

    //         $query->where(
    //             fn($q) =>
    //             $q->where('name', 'LIKE', "%{$s}%")
    //                 ->orWhereHas(
    //                     'client',
    //                     fn($cq) => $cq->where('name', 'LIKE', "%{$s}%")
    //                 )
    //         );
    //     }

    //     $gyms = $query->orderBy('created_at', 'desc')->get();

    //     $all = Gym::with('branches')->get();

    //     return [
    //         'metrics' => [
    //             'total' => $all->count(),
    //             'active' => $all->where('is_active', 1)->count(),
    //             'inactive' => $all->where('is_active', 0)->count(),
    //             'total_branches' => $all->sum(fn($g) => $g->branches->count()),
    //         ],
    //         'data' => $gyms->map(fn($g) => $this->formatGym($g)),
    //     ];
    // }

public function getGyms(Request $request): array
{
    $user = $request->user(); // ✅ usa el guard que configuró jwt.auth

    if (!$user) {
        abort(401, 'No autenticado.');
    }

    $role = $user->modelHasRole?->role?->name;

    $query = Gym::with(['branches', 'client']);

    match ($role) {
        'owner' => $query->where('owner_id', $user->id),

        'coach' => $query->whereHas(
            'branches.users',
            fn($q) => $q->where('users.id', $user->id)
        ),

        'client' => $query->whereHas(
            'branches.users',
            fn($q) => $q->where('users.id', $user->id)
        ),

        default => null
    };

    if ($request->has('status')) {
        match ($request->status) {
            'active'   => $query->where('is_active', 1),
            'inactive' => $query->where('is_active', 0),
            default    => null,
        };
    }

    if ($request->filled('client_id')) {
        $query->where('system_client_id', $request->client_id);
    }

    if ($request->filled('search')) {
        $s = $request->search;
        $query->where(
            fn($q) =>
            $q->where('name', 'LIKE', "%{$s}%")
                ->orWhereHas(
                    'client',
                    fn($cq) => $cq->where('name', 'LIKE', "%{$s}%")
                )
        );
    }

    $gyms = $query->orderBy('created_at', 'desc')->get();

    return [
        'metrics' => [
            'total'          => $gyms->count(),
            'active'         => $gyms->where('is_active', 1)->count(),
            'inactive'       => $gyms->where('is_active', 0)->count(),
            'total_branches' => $gyms->sum(fn($g) => $g->branches->count()),
        ],
        'data' => $gyms->map(fn($g) => $this->formatGym($g)),
    ];
}


    public function getGym(int $id): array
    {
        $gym = Gym::with(['branches', 'client'])->findOrFail($id);

        return $this->formatGymDetail($gym);
    }

    public function createGym(array $data): Gym
    {
        return Gym::create($data);
    }

    public function updateGym(int $id, array $data): Gym
    {
        $gym = Gym::findOrFail($id);

        $gym->update($data);

        return $gym->fresh(['branches', 'client']);
    }

    public function toggleGym(int $id): array
    {
        $gym = Gym::with('branches')->findOrFail($id);

        $status = !$gym->is_active;

        $gym->update([
            'is_active' => $status
        ]);

        $gym->branches()->update([
            'is_active' => $status
        ]);

        return [
            'message' => $status
                ? 'Gym activado correctamente.'
                : 'Gym desactivado correctamente.',
            'is_active' => $status
        ];
    }

    public function deleteGym(int $id): void
    {
        Gym::findOrFail($id)->delete();
    }

    public function getClients(?int $includeId = null)
    {
        $query = SystemClient::select('id', 'name', 'email');

        if ($includeId) {
            $query->where(
                fn($q) =>
                $q->where('is_active', 1)
                    ->orWhere('id', $includeId)
            );
        } else {
            $query->where('is_active', 1);
        }

        return $query->orderBy('name')->get();
    }

    private function formatGym(Gym $gym): array
    {
        return [
            'id' => $gym->id,
            'name' => $gym->name,
            'address' => $gym->address,
            'phone' => $gym->phone,
            'is_active' => (bool) $gym->is_active,
            'branch_count' => $gym->branches->count(),
            'status_label' => $gym->is_active ? 'Activo' : 'Inactivo',
            'status_color' => $gym->is_active ? 'green' : 'gray',
            'created_at' => $gym->created_at,
            'system_client_id' => $gym->system_client_id,
            'client' => $gym->client,
        ];
    }

    private function formatGymDetail(Gym $gym): array
    {
        $data = $this->formatGym($gym);

        $data['branches'] = $gym->branches->map(fn($b) => [
            'id' => $b->id,
            'name' => $b->name,
            'address' => $b->address,
            'phone' => $b->phone,
            'is_active' => (bool) $b->is_active,
        ]);

        return $data;
    }
}