<?php

namespace App\Http\Controllers\Suadmin\users;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthUserResource;
use App\Http\Resources\UsersLoandingResource;
use App\Models\Gym;
use App\Models\Role;
use App\Models\SubRole;
use App\Models\ModelHasRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    // ─── READ ────────────────────────────────────────────────────────────────
    private function transformUser(User $user)
    {
        return (new AuthUserResource($user))
            ->toArray(request());
    }

    public function users()
    {
        $authUser = auth()->user();
        $authId   = $authUser->id;
        $authRole = $authUser->modelHasRole?->role?->name;

        if ($authRole === 'superadmin') {
            return $this->usersForSuperadmin($authId);
        }

        if ($authRole === 'gym_owner') {
            return $this->usersForGymOwner($authUser);
        }

        if (in_array($authRole, ['admin', 'coach'], true)) {
            return $this->usersForStaff($authUser);
        }

        return $this->usersForClient($authUser);
    }

    public function show($id)
    {
        $user = User::with([
            'gym',
            'branch',
            'modelHasRole.role',
            'modelHasRole.subRole',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'user' => $user]);
    }

    // ─── WRITE ───────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $authRole = auth()->user()?->modelHasRole?->role?->name;

        // Solo superadmin necesita contraseña maestra
        if ($authRole === 'superadmin' && !$this->verificarContrasenaMaestra($request)) {
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
            'name.required'     => 'El nombre es requerido.',
            'email.required'    => 'El correo es requerido.',
            'email.email'       => 'El correo no tiene un formato válido.',
            'email.unique'      => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es requerida.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $validated['password'] = bcrypt($validated['password']);

        $roleId    = $validated['role_id'] ?? null;
        $subRoleId = $validated['sub_role_id'] ?? null;
        unset($validated['role_id'], $validated['sub_role_id']);

        $user = User::create($validated);

        if ($roleId) {
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
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $authRole = auth()->user()?->modelHasRole?->role?->name;

        if ($authRole === 'superadmin' && !$this->verificarContrasenaMaestra($request)) {
            return response()->json(['success' => false, 'message' => 'Contraseña maestra incorrecta.'], 403);
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
            'email.email'  => 'El correo no tiene un formato válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $roleId    = $validated['role_id'] ?? null;
        $subRoleId = $validated['sub_role_id'] ?? null;
        unset($validated['role_id'], $validated['sub_role_id']);

        $user->update($validated);

        if ($roleId) {
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
        $authRole = auth()->user()?->modelHasRole?->role?->name;

        if ($authRole === 'superadmin' && !$this->verificarContrasenaMaestra($request)) {
            return response()->json(['success' => false, 'message' => 'Contraseña maestra incorrecta.'], 403);
        }

        User::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ─── PRIVATE: vistas por rol ─────────────────────────────────────────────

    private function usersForSuperadmin(int $authId): JsonResponse
    {
        $users = User::with([
            'gym',
            'branch',          // ✅
            'modelHasRole.role',
            'modelHasRole.subRole',
            'memberships',
            'membership',
            'qrTokens',
            'accessLogs',
            'biometrics',
            'workouts',
            'coaches',
        ])
            ->where('email', '!=', 'skrip5025@gmail.com')
            ->get();

        $superadmins  = [];
        $admins       = [];
        $clientsByGym = [];
        $adminRoles   = ['admin', 'gym_owner'];

        foreach ($users as $user) {
            $roleName = $user->modelHasRole?->role?->name;
            $payload = $this->transformUser($user);

            if ($roleName === 'superadmin') {
                if ($user->id === $authId) continue;
                $superadmins[] = $payload;
                continue;
            }

            if (in_array($roleName, $adminRoles, true)) {
                $admins[] = $payload;
                continue;
            }

            $gymKey = $user->gym?->id ?? 'general';
            if (!isset($clientsByGym[$gymKey])) {
                $clientsByGym[$gymKey] = [
                    'gym'   => $user->gym ? ['id' => $user->gym->id, 'name' => $user->gym->name] : null,
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

    private function usersForGymOwner($authUser): JsonResponse
    {
        $gymIds = Gym::where('owner_id', $authUser->id)->pluck('id')->toArray();

        $users = User::with(['gym', 'branch', 'modelHasRole.role', 'modelHasRole.subRole', 'membership', 'coaches',])
            ->whereIn('gym_id', $gymIds)
            ->whereHas('modelHasRole.role', fn($q) => $q->whereIn('name', ['client', 'coach', 'admin']))
            ->get();
        $admins       = [];
        $clientsByGym = [];
        $staffRoles   = ['admin', 'coach'];

        foreach ($users as $user) {
            $roleName = $user->modelHasRole?->role?->name;
            $payload = (new AuthUserResource($user))->toArray(request());

            // admin y coach van a la sección "Admins" del frontend
            if (in_array($roleName, $staffRoles, true)) {
                $admins[] = $payload;
                continue;
            }

            // el resto (client) va agrupado por gym
            $gymKey = $user->gym?->id ?? 'general';
            if (!isset($clientsByGym[$gymKey])) {
                $clientsByGym[$gymKey] = [
                    'gym'   => $user->gym ? ['id' => $user->gym->id, 'name' => $user->gym->name] : null,
                    'users' => [],
                ];
            }
            $clientsByGym[$gymKey]['users'][] = $payload;
        }

        // Contacto del sistema
        $systemOwner = User::with(['modelHasRole.role'])
            ->where('email', 'skrip5025@gmail.com')
            ->first();

        return response()->json([
            'success'     => true,
            'superadmins' => $systemOwner ? [$this->transformUser($systemOwner)] : [],
            'admins'      => $admins,
            'clients'     => array_values($clientsByGym),
        ]);
    }

    private function usersForStaff($authUser): JsonResponse
    {
        $gymId    = $authUser->gym_id;
        $authRole = $authUser->modelHasRole?->role?->name;

        if (!$gymId) {
            return response()->json([
                'success'     => true,
                'superadmins' => [],
                'admins'      => [],
                'clients'     => [],
                '_debug'      => [
                    'mensaje'  => 'El usuario staff no tiene gym_id asignado en la tabla users.',
                    'user_id'  => $authUser->id,
                    'gym_id'   => $authUser->gym_id,
                    'role'     => $authUser->modelHasRole?->role?->name,
                    'sub_role' => $authUser->modelHasRole?->subRole?->name,
                ],
            ]);
        }

        if ($authRole === 'coach') {
            $clientIds = DB::table('coach_user')
                ->where('coach_id', $authUser->id)
                ->pluck('user_id');

            $clients = User::with(['gym', 'branch', 'modelHasRole.role', 'modelHasRole.subRole', 'membership', 'coaches'])
                ->whereIn('id', $clientIds)
                ->get();
        } else {
            // admin / branch_manager ve todos los clientes del gym
            $clients = User::with(['gym', 'branch', 'modelHasRole.role', 'modelHasRole.subRole', 'membership', 'coaches'])
                ->where('gym_id', $gymId)
                ->whereHas('modelHasRole.role', fn($q) => $q->where('name', 'client'))
                ->get();
        }
        // 🔥 Admins, coaches y gym_owners del mismo gym
        $admins = User::with(['gym', 'branch', 'modelHasRole.role', 'modelHasRole.subRole', 'membership', 'coaches'])
            ->where('gym_id', $gymId)
            ->where('id', '!=', $authUser->id)
            ->whereHas('modelHasRole.role', fn($q) => $q->whereIn('name', ['admin', 'coach', 'gym_owner']))
            ->get()
            ->map(fn($u) => $this->transformUser($u));

        // 🔥 Clientes del mismo gym
        $clients = User::with(['gym', 'branch', 'modelHasRole.role', 'modelHasRole.subRole', 'membership', 'coaches'])
            ->where('gym_id', $gymId)
            ->whereHas('modelHasRole.role', fn($q) => $q->where('name', 'client'))
            ->get();

        $clientsByGym = [];
        foreach ($clients as $user) {
            $gymKey = $user->gym?->id ?? 'general';
            if (!isset($clientsByGym[$gymKey])) {
                $clientsByGym[$gymKey] = [
                    'gym'   => $user->gym ? ['id' => $user->gym->id, 'name' => $user->gym->name] : null,
                    'users' => [],
                ];
            }
            $clientsByGym[$gymKey]['users'][] = $this->transformUser($user);
        }

        return response()->json([
            'success'     => true,
            'superadmins' => [],
            'admins'      => $admins->values(),
            'clients'     => array_values($clientsByGym),
        ]);
    }

    private function usersForClient($authUser): JsonResponse
    {
        $user = User::with(['gym', 'branch', 'modelHasRole.role', 'modelHasRole.subRole'])
            ->findOrFail($authUser->id);

        return response()->json([
            'success'     => true,
            'superadmins' => [],
            'admins'      => [],
            'clients'     => [[
                'gym'   => $user->gym ? ['id' => $user->gym->id, 'name' => $user->gym->name] : null,
                'users' => [$this->transformUser($user)],
            ]],
        ]);
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────────

    // private function buildPayload(User $user): array
    // {
    //     return [
    //         'id'        => $user->id,
    //         'name'      => $user->name,
    //         'email'     => $user->email,
    //         'type'      => $user->type,
    //         'is_active' => $user->is_active,
    //         'gym'       => $user->gym
    //             ? ['id' => $user->gym->id, 'name' => $user->gym->name]
    //             : null,
    //         'branch'    => $user->branch
    //             ? ['id' => $user->branch->id, 'name' => $user->branch->name]
    //             : null,
    //         'role'      => $user->modelHasRole?->role
    //             ? ['id' => $user->modelHasRole->role->id, 'name' => $user->modelHasRole->role->name]
    //             : null,
    //         'sub_role'  => $user->modelHasRole?->subRole
    //             ? ['id' => $user->modelHasRole->subRole->id, 'name' => $user->modelHasRole->subRole->name]
    //             : null,
    //     ];
    // }

    private function verificarContrasenaMaestra(Request $request): bool
    {
        $maestro = User::where('email', 'skrip5025@gmail.com')->first();
        if (!$maestro) return false;
        return Hash::check($request->input('master_password'), $maestro->password);
    }
}