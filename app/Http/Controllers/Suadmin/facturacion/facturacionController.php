<?php

namespace App\Http\Controllers\Suadmin\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\SystemClient;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class FacturacionController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = SystemClient::with(['gyms.branches'])->get();

        // ── Métricas calculadas desde system_clients ──────────────
        $cobrado   = $clients->where('is_active', 1)->count();  // temporal hasta tener pagos reales
        $porVencer = $clients->filter(fn($c) => $this->daysLeft($c) <= 7 && $this->daysLeft($c) > 0)->count();
        $vencidos  = $clients->filter(fn($c) => $this->daysLeft($c) <= 0)->count();

        $metrics = [
            ['label' => 'Clientes activos',   'value' => $cobrado,   'color' => 'success'],
            ['label' => 'Por vencer (7 días)', 'value' => $porVencer, 'color' => 'warning'],
            ['label' => 'Vencidos',            'value' => $vencidos,  'color' => 'danger'],
        ];

        // ── Lista de system_clients ───────────────────────────────
        $items = $clients->map(function ($client) {
            $daysLeft   = $this->daysLeft($client);
            $gymCount   = $client->gyms->count();
            $branchCount = $client->gyms->sum(fn($g) => $g->branches->count());

            [$badge, $badgeColor] = match(true) {
                $daysLeft <= 0  => ['Vencido',                'red'],
                $daysLeft <= 3  => ["Vence en {$daysLeft} días", 'red'],
                $daysLeft <= 7  => ["Vence en {$daysLeft} días", 'yellow'],
                default         => ['Al día',                 'green'],
            };

            return [
                'name'       => $client->name,
                'sub'        => "{$gymCount} gym(s) · {$branchCount} sucursal(es) · vence {$client->subscription_end}",
                'badge'      => $badge,
                'badgeColor' => $badgeColor,
            ];
        })->values();

        return response()->json([
            'metrics' => $metrics,
            'items'   => $items,
        ]);
    }

    private function daysLeft(SystemClient $client): int
    {
        if (!$client->subscription_end) return -1;
        return (int) Carbon::today()->diffInDays($client->subscription_end, false);
    }
}