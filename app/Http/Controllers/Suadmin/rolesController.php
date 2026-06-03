<?php

namespace App\Http\Controllers\Suadmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SubRole;
use App\Models\ModelHasRole;

class RolesController extends Controller
{
    public function index()
    {
        $roles = Role::all()->map(function ($role) {

            $usersCount = ModelHasRole::where('role_id', $role->id)
                ->distinct('model_id')
                ->count('model_id');

            return [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $usersCount,
            ];
        });

        $subRoles = SubRole::all()->map(function ($subRole) {

            $usersCount = ModelHasRole::where('sub_role_id', $subRole->id)
                ->distinct('model_id')
                ->count('model_id');

            return [
                'id' => $subRole->id,
                'name' => $subRole->name,
                'users_count' => $usersCount,
            ];
        });

        return response()->json([
            'success' => true,
            'roles' => $roles,
            'sub_roles' => $subRoles,
        ]);
    }
}