<?php

namespace App\Http\Controllers\Suadmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SubRole;
use App\Models\ModelHasRole;
use Illuminate\Http\Request;

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



    // =========================
    // ROLES CRUD
    // =========================

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'api',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role creado correctamente',
            'role' => $role
        ]);
    }

    public function showRole($id)
    {
        $role = Role::findOrFail($id);

        return response()->json([
            'success' => true,
            'role' => $role
        ]);
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
        ]);

        $role = Role::findOrFail($id);
        $role->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role actualizado correctamente',
            'role' => $role
        ]);
    }

    public function destroyRole($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role eliminado correctamente'
        ]);
    }

    // =========================
    // SUBROLES CRUD
    // =========================

    public function storeSubRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sub_roles,name',
            'description' => 'required|string|max:255',
        ]);

        $subRole = SubRole::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'SubRole creado correctamente',
            'sub_role' => $subRole
        ]);
    }

    public function showSubRole($id)
    {
        $subRole = SubRole::findOrFail($id);

        return response()->json([
            'success' => true,
            'sub_role' => $subRole
        ]);
    }

    public function updateSubRole(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sub_roles,name,' . $id,
            'description' => 'required|string|max:255',
        ]);

        $subRole = SubRole::findOrFail($id);

        $subRole->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'SubRole actualizado correctamente',
            'sub_role' => $subRole
        ]);
    }
    public function destroySubRole($id)
    {
        $subRole = SubRole::findOrFail($id);
        $subRole->delete();

        return response()->json([
            'success' => true,
            'message' => 'SubRole eliminado correctamente'
        ]);
    }


    public function indexSubRoles()
    {
        $subRoles = SubRole::all();

        return response()->json([
            'success' => true,
            'sub_roles' => $subRoles
        ]);
    }
}
