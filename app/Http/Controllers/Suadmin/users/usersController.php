<?php

namespace App\Http\Controllers\Suadmin\users;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SubRole;
use App\Models\ModelHasRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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


    public function store(Request $request)
    {

        if (!$this->verificarContrasenaMaestra($request)) {
            return response()->json(['success' => false, 'message' => 'Contraseña maestra incorrecta.'], 403);
        }
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8',
            'gym_id'      => 'nullable|exists:gyms,id',
            'type'        => 'nullable|string|in:n,client,coach,admin,owner',
            'is_active'   => 'nullable|boolean',
            'role_id'     => 'nullable|exists:roles,id',
            'sub_role_id' => 'nullable|exists:sub_roles,id',
        ], [
            // 👇 Este segundo parámetro es el que faltaba o estaba mal puesto
            'name.required'    => 'El nombre es requerido.',
            'email.required'   => 'El correo es requerido.',
            'email.email'      => 'El correo no tiene un formato válido.',
            'email.unique'     => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es requerida.',
            'password.min'     => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $validated['password'] = bcrypt($validated['password']);

        // Extraer role_id y sub_role_id antes de crear el user
        $roleId    = $validated['role_id'] ?? null;
        $subRoleId = $validated['sub_role_id'] ?? null;

        unset($validated['role_id'], $validated['sub_role_id']);

        $user = User::create($validated);

        // Crear model_has_roles si viene role_id
        if ($roleId) {
            ModelHasRole::create([
                'role_id'    => $roleId,
                'sub_role_id' => $subRoleId,
                'model_type' => User::class,
                'model_id'   => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'user'    => $user->load('modelHasRole.role', 'modelHasRole.subRole'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if (!$this->verificarContrasenaMaestra($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Contraseña maestra incorrecta.'
            ], 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'email'       => 'sometimes|email|unique:users,email,' . $id,
            'password'    => 'sometimes|string|min:8',
            'gym_id'      => 'nullable|exists:gyms,id',
            'type'        => 'nullable|string|in:n,client,coach,admin,owner',
            'is_active'   => 'nullable|boolean',
            'role_id'     => 'nullable|exists:roles,id',
            'sub_role_id' => 'nullable|exists:sub_roles,id',
        ], [
            'email.email'   => 'El correo no tiene un formato válido.',
            'email.unique'  => 'Este correo ya está registrado.',
            'password.min'  => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        // password hash si viene
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        // separar roles
        $roleId    = $validated['role_id'] ?? null;
        $subRoleId = $validated['sub_role_id'] ?? null;

        unset($validated['role_id'], $validated['sub_role_id']);

        // actualizar usuario
        $user->update($validated);

        // 🔥 actualizar relación (IMPORTANTE: evitar duplicados)
        if ($roleId) {

            // opcional: eliminar anterior
            ModelHasRole::where('model_type', User::class)
                ->where('model_id', $user->id)
                ->delete();

            ModelHasRole::create([
                'role_id'     => $roleId,
                'sub_role_id' => $subRoleId,
                'model_type'  => User::class,
                'model_id'    => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'user'    => $user->load('modelHasRole.role', 'modelHasRole.subRole'),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        if (!$this->verificarContrasenaMaestra($request)) {
            return response()->json(['success' => false, 'message' => 'Contraseña maestra incorrecta.'], 403);
        }
        User::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $user = User::with([
            'gym',
            'branch',
            'modelHasRole.role',
            'modelHasRole.subRole',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'user'    => $user,
        ]);
    }

    private function verificarContrasenaMaestra(Request $request): bool
    {
        $maestro = User::where('email', 'skrip5025@gmail.com')->first();
        if (!$maestro) return false;
        return Hash::check($request->input('master_password'), $maestro->password);
    }
}