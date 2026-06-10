<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $role = $this->roles->first();
        $roleId = $role?->id ?? 5;

        // 🔥 membresía activa
        $membership = $this->memberships
            ->sortByDesc('end_date')
            ->first();

        $membershipDaysLeft = $membership
            ? max($membership->remainingDays(), 0)
            : 0;

        $membershipActive = $membership
            ? $membership->isValid()
            : false;

        // 🔥 último qr
        $qr = $this->qrTokens
            ->sortByDesc('expires_at')
            ->first();

        // 🔥 último acceso (lo dejamos para compatibilidad)
        $lastAccess = $this->accessLogs
            ->sortByDesc('accessed_at')
            ->first();

        // 🔥 historial agrupado por fecha
        $accessSessions = $this->accessLogs
            ->sortBy('accessed_at')
            ->groupBy(fn($log) => \Carbon\Carbon::parse($log->accessed_at)->format('Y-m-d'))
            ->map(function ($logs, $date) {
                $entrada = $logs->where('access_type', 'qr')->first()
                    ?? $logs->where('access_type', 'biometric')->first()
                    ?? $logs->first();

                $salida = $logs->count() > 1 ? $logs->last() : null;

                return [
                    'date'    => $date,
                    'entrada' => $entrada ? [
                        'at'     => $entrada->accessed_at,
                        'method' => $entrada->access_type,
                    ] : null,
                    'salida'  => $salida ? [
                        'at'     => $salida->accessed_at,
                        'method' => $salida->access_type,
                    ] : null,
                ];
            })
            ->values();


        // 🔥 workout actual
        $workout = $this->workouts
            ->first();

        return [

            // ─────────────────────────────
            // Basic
            // ─────────────────────────────
            'id' => $this->id,
            'gym_id' => $this->gym_id,

            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type,

            'is_active' => (bool) $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // ─────────────────────────────
            // Roles
            // ─────────────────────────────
            'permissions' => $roleId,

            // ─────────────────────────────
            // Gym
            // ─────────────────────────────
            'gym' => $this->gym ? [
                'id' => $this->gym->id,
                'name' => $this->gym->name,
                'address' => $this->gym->address,
                'phone' => $this->gym->phone,
                'is_active' => (bool) $this->gym->is_active,
            ] : null,

            // ─────────────────────────────
            // Branch
            // ─────────────────────────────
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'address' => $this->branch->address,
            ] : null,

            // ─────────────────────────────
            // Membership
            // ─────────────────────────────
            'membership' => $membership ? [
                'id' => $membership->id,
                'type' => $membership->type,
                'price' => $membership->price,

                'start_date' => $membership->start_date,
                'end_date' => $membership->end_date,

                'days_left' => $membershipDaysLeft,

                'is_active' => $membershipActive,
            ] : null,

            // ─────────────────────────────
            // QR
            // ─────────────────────────────
            'qr' => $qr ? [
                'token' => $qr->token,
                'is_active' => (bool) $qr->is_active,
                'last_used_at' => $qr->last_used_at,
            ] : null,

            // ─────────────────────────────
            // Biometrics
            // ─────────────────────────────
            'biometrics' => [
                'registered' => $this->biometrics->count() > 0,
                'total' => $this->biometrics->count(),
            ],

            // ─────────────────────────────
            // Access
            // ─────────────────────────────
            'last_access' => $lastAccess ? [
                'type' => $lastAccess->access_type,
                'accessed_at' => $lastAccess->accessed_at,
            ] : null,

            'access_sessions' => $accessSessions,

            // ─────────────────────────────
            // Workout
            // ─────────────────────────────
            'workout' => $workout ? [
                'id' => $workout->id,
                'title' => $workout->title,
                'description' => $workout->description,
                'exercises' => $workout->exercises,
            ] : null,
        ];
    }
}