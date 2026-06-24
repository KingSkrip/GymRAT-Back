<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\JwtHelper;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token requerido'], 401);
        }

        try {
            $decoded = JwtHelper::validateToken($token);
            $user = User::findOrFail($decoded->user_id);

            Auth::setUser($user);
            $request->setUserResolver(fn() => $user);
            $request->auth = $decoded;

        } catch (Exception $e) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        return $next($request);
    }
}