<?php

namespace App\Services\Clientes;

use App\Models\SystemClient;
use Carbon\Carbon;

class ClienteService
{
    public function formatClient(SystemClient $client): array
    {
        $daysLeft    = $this->daysLeft($client);
        $gymCount    = $client->gyms->count();
        $branchCount = $client->gyms->sum(fn($g) => $g->branches->count());
        [$statusLabel, $statusColor] = $this->resolveStatus($client, $daysLeft);

        return [
            'id'                 => $client->id,
            'name'               => $client->user?->name,
            'email'              => $client->user?->email,
            'phone'              => $client->user?->phone,
            'is_active'          => (bool) $client->is_active,
            'subscription_start' => $client->subscription_start,
            'subscription_end'   => $client->subscription_end,
            'days_left'          => $daysLeft,
            'gym_count'          => $gymCount,
            'branch_count'       => $branchCount,
            'status_label'       => $statusLabel,
            'status_color'       => $statusColor,
            'created_at'         => $client->created_at,
        ];
    }

    public function formatClientDetail(SystemClient $client): array
    {
        $base = $this->formatClient($client);

        $base['gyms'] = $client->gyms->map(fn($gym) => [
            'id'           => $gym->id,
            'name'         => $gym->name,
            'address'      => $gym->address,
            'phone'        => $gym->phone,
            'is_active'    => (bool) $gym->is_active,
            'branch_count' => $gym->branches->count(),
            'branches'     => $gym->branches->map(fn($b) => [
                'id'        => $b->id,
                'name'      => $b->name,
                'address'   => $b->address,
                'is_active' => (bool) $b->is_active,
            ]),
        ]);

        return $base;
    }

    public function daysLeft(SystemClient $client): int
    {
        if (!$client->subscription_end) return -1;
        return (int) Carbon::today()->diffInDays($client->subscription_end, false);
    }

    public function resolveStatus(SystemClient $client, int $daysLeft): array
    {
        if (!$client->is_active) return ['Inactivo',             'gray'];
        if ($daysLeft <= 0)      return ['Vencido',              'red'];
        if ($daysLeft <= 3)      return ["Vence en {$daysLeft}d", 'red'];
        if ($daysLeft <= 7)      return ["Vence en {$daysLeft}d", 'yellow'];
        return                          ['Activo',               'green'];
    }
}