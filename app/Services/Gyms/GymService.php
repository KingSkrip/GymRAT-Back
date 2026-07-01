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
    $user = $request->user();

    if (!$user) {
        abort(401, 'No autenticado.');
    }

    $role = $user->modelHasRole?->role?->name;

    $query = Gym::with(['branches', 'client.user']);

    match ($role) {
        // Superadmin: ve todo, sin filtro
        'superadmin' => null,

        // Gym Owner: solo los gyms donde es dueño (gyms.owner_id)
        'gym_owner' => $query->where('owner_id', $user->id),

        // Admin: los gyms donde está asignado (users.gym_id)
        'admin' => $query->where('id', $user->gym_id),

        // Coach: los gyms donde trabaja (users.gym_id)
        'coach' => $query->where('id', $user->gym_id),

        // Client: los gyms donde tiene registro (users.gym_id)
        'client' => $query->where('id', $user->gym_id),

        default => abort(403, 'Rol no reconocido.')
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
            $q->where('gyms.name', 'LIKE', "%{$s}%")
                ->orWhereHas(
                    'client.user',
                    fn($cq) => $cq->where('name', 'LIKE', "%{$s}%")
                )
        );
    }

    $gyms = $query->orderBy('gyms.created_at', 'desc')->get();

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
        $gym = Gym::with(['branches', 'client.user'])->findOrFail($id);  // ← .user

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
        $query = SystemClient::select('system_clients.id', 'users.name', 'users.email')
            ->join('users', 'users.id', '=', 'system_clients.user_id');

        if ($includeId) {
            $query->where(
                fn($q) =>
                $q->where('system_clients.is_active', 1)
                    ->orWhere('system_clients.id', $includeId)
            );
        } else {
            $query->where('system_clients.is_active', 1);
        }

        return $query->orderBy('users.name')->get();
    }

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
            'client' => $gym->client ? [
                'id'    => $gym->client->id,
                'name'  => $gym->client->user?->name,
                'email' => $gym->client->user?->email,
            ] : null,
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