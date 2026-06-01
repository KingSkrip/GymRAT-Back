<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\QrToken;
use App\Models\AccessLog;
use Illuminate\Http\Request;

class QrController extends Controller
{
    public function validateQr(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        // 🔍 buscar qr
        $qr = QrToken::with('user.memberships')
            ->where('token', $request->token)
            ->where('is_active', true)
            ->first();

        // ❌ QR inválido
        if (!$qr) {
            return response()->json([
                'success' => false,
                'message' => 'QR inválido'
            ], 404);
        }

        $user = $qr->user;

        // 🚫 usuario inactivo
        if (!$user || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo'
            ], 403);
        }

        // 🔥 membresía activa
        $membership = $user->memberships
            ->sortByDesc('end_date')
            ->first();

        if (
            !$membership ||
            !$membership->isValid()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Membresía vencida'
            ], 403);
        }

        // ✅ registrar acceso
        AccessLog::create([
            'user_id' => $user->id,
            'gym_id' => $user->gym_id,
            'access_type' => 'qr',
            'accessed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Acceso permitido',

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ]
        ]);
    }
}