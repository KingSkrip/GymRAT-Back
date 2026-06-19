<?php

namespace App\Http\Controllers\Suadmin\users;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SubRole;
use App\Models\ModelHasRole;
use App\Models\User;

class UsersController extends Controller
{
    public function users()
    {
        $authId = auth()->id();
        $adminRoles = ['admin', 'gym_owner'];

        $users = User::with([
            'gym',
            'branch',
            'modelHasRole.role',
            'modelHasRole.subRole',
        ])
            ->where('email', '!=', 'skrip5025@gmail.com')
            ->get();

        $superadmins = [];
        $admins = [];
        $clientsByGym = [];

        foreach ($users as $user) {
            $roleName = $user->modelHasRole?->role?->name;

            $payload = [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'type'      => $user->type,
                'is_active' => $user->is_active,
                'gym'       => $user->gym
                    ? ['id' => $user->gym->id, 'name' => $user->gym->name]
                    : null,
                'branch'    => $user->branch
                    ? ['id' => $user->branch->id, 'name' => $user->branch->name]
                    : null,
                'role'      => $user->modelHasRole?->role
                    ? ['id' => $user->modelHasRole->role->id, 'name' => $user->modelHasRole->role->name]
                    : null,
                'sub_role'  => $user->modelHasRole?->subRole
                    ? ['id' => $user->modelHasRole->subRole->id, 'name' => $user->modelHasRole->subRole->name]
                    : null,
            ];

            if ($roleName === 'superadmin') {
                if ($user->id === $authId) {
                    continue; // tú, el creador, no apareces en tu propia lista
                }
                $superadmins[] = $payload;
                continue;
            }

            if (in_array($roleName, $adminRoles, true)) {
                $admins[] = $payload;
                continue;
            }

            // todo lo demás cuenta como cliente, agrupado por gym
            $gymKey = $user->gym->id ?? 'general';

            if (!isset($clientsByGym[$gymKey])) {
                $clientsByGym[$gymKey] = [
                    'gym'   => $user->gym
                        ? ['id' => $user->gym->id, 'name' => $user->gym->name]
                        : null,
                    'users' => [],
                ];
            }

            $clientsByGym[$gymKey]['users'][] = $payload;
        }

        return response()->json([
            'success'     => true,
            'superadmins' => $superadmins,
            'admins'      => $admins,
            'clients'     => array_values($clientsByGym),
        ]);
    }
}