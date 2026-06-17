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
        $allowedRoles = ['superadmin', 'gym_owner', 'admin'];

     $users = User::with([
        'gym',
        'branch',                    // ← ya existe en el modelo
        'modelHasRole.role',
        'modelHasRole.subRole',
    ])
    ->whereHas('modelHasRole.role', fn($q) =>
        $q->whereIn('name', $allowedRoles)
    )
    ->get()
    ->map(function ($user) {
        $modelRole = $user->modelHasRole;
        return [
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
            'role'      => $modelRole?->role
                ? ['id' => $modelRole->role->id, 'name' => $modelRole->role->name]
                : null,
            'sub_role'  => $modelRole?->subRole
                ? ['id' => $modelRole->subRole->id, 'name' => $modelRole->subRole->name]
                : null,
        ];
    });

        return response()->json(['success' => true, 'users' => $users]);
    }

    
}