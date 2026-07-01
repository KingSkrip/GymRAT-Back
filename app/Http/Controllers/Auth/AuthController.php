<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Helpers\JwtHelper;
use App\Http\Resources\AuthUserResource;
use App\Models\QrToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    // 🔐 LOGIN
    public function signIn(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::with([
            'roles',
            'gym',
            'branch',
            'memberships',
            'biometrics',
            'qrTokens',
            'accessLogs',
            'workouts'
        ])
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // 🚨 Gym suspendido
        if ($user->gym && !$user->gym->is_active) {
            return response()->json([
                'message' => 'El gimnasio ha sido suspendido'
            ], 403);
        }

        // 👑 role principal
        $role = $user->roles->first();

        $roleId = $role?->id ?? 5;

        // 🔐 token
        $token = JwtHelper::generateToken([
            'user_id' => $user->id,
            'email' => $user->email,
            'permissions' => $roleId,
            'gym_id' => $user->gym_id
        ]);

        return response()->json([
            'message' => 'Login exitoso',
            'encrypt' => $token,
            'permissions' => $roleId,
            'user' => new AuthUserResource($user)
        ]);
    }

    public function signInWithToken(Request $request)
    {
        try {
            $token = $request->encrypt;
            if (!$token) {
                return response()->json(['message' => 'Token requerido'], 401);
            }
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $user = User::with([
                'roles',
                'gym',
                'branch',
                'memberships',
                'biometrics',
                'qrTokens',
                'accessLogs',
                'workouts'
            ])->find($decoded->user_id);
            if (!$user) {
                return response()->json(['message' => 'Usuario no encontrado'], 401);
            }
            $role = $user->roles->first();
            $roleId = $role?->id ?? $decoded->permissions ?? 5;

            return response()->json([
                'encrypt' => $token,
                'permissions' => $roleId,
                'user' => new AuthUserResource($user)
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Token inválido'], 401);
        }
    }

    // 🧾 REGISTER
    public function signUp(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'gym_id' => $request->gym_id ?? null,
            'type' => 'client',
            'is_active' => true
        ]);

        QrToken::create([
            'user_id' => $user->id,
            'gym_id' => $user->gym_id,

            'token' => hash(
                'sha256',
                Str::uuid() .
                    random_bytes(32) .
                    microtime(true)
            ),

            'is_active' => true,
        ]);

        // 👇 rol por defecto (client)
        $clientRole = DB::table('roles')->where('name', 'client')->first();

        if ($clientRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $clientRole->id,
                'model_type' => User::class,
                'model_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $token = JwtHelper::generateToken([
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => 'client',
            'gym_id' => $user->gym_id
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'token' => $token,
            'user' => $user
        ], 201);
    }

    // 🚪 LOGOUT
    public function signOut(Request $request)
    {
        return response()->json([
            'message' => 'Logout exitoso (elimina token en frontend)'
        ]);
    }

    // 📩 FORGOT PASSWORD
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Si el correo existe, se enviará un enlace'
            ]);
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        Mail::raw(
            "Tu token de recuperación es: $token",
            function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Recuperación de contraseña Gym_Rat');
            }
        );

        return response()->json([
            'message' => 'Correo de recuperación enviado'
        ]);
    }

    // 🔁 RESET PASSWORD
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6'
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json([
                'message' => 'Token inválido o expirado'
            ], 400);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }


    public function me(Request $request)
    {
        try {
            $user = User::with([
                'roles',
                'gym',
                'branch',
                'memberships',
                'biometrics',
                'qrTokens',
                'accessLogs',
                'workouts'
            ])->find($request->auth->user_id);

            if (!$user) {
                return response()->json(['message' => 'Usuario no encontrado'], 401);
            }

            $resourceArray = (new AuthUserResource($user))->toArray($request);

            // 👇 DEBUG TEMPORAL
            $json = json_encode(['user' => $resourceArray]);
            if ($json === false) {
                Log::channel('single')->error('JSON ENCODE FAILED: ' . json_last_error_msg());
                return response()->json(['message' => 'JSON encode error: ' . json_last_error_msg()], 500);
            }

            return response($json, 200)->header('Content-Type', 'application/json');
        } catch (Throwable $e) {
            Log::channel('single')->error('ME ERROR: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}