<?php

namespace App\Http\Controllers\Suadmin\Sucursales;

use App\Http\Controllers\Controller;
use App\Services\Sucursales\SucursalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SucursalesController extends Controller
{
    public function __construct(protected SucursalesService $service) {}

    // ── GET /gestion/sucursales ───────────────────────────────────────
  public function index(Request $request): JsonResponse
{
    $filters = $request->only(['status', 'gym_id', 'search', 'sub_status']);

    return response()->json([
        'metrics' => $this->service->getMetrics($request->user()),
        'data'    => $this->service->getBranches($filters, $request->user()),
    ]);
}

    // ── GET /gestion/sucursales/{id} ──────────────────────────────────
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->service->getBranchWithSubscriptions($id),
        ]);
    }

    // ── POST /gestion/sucursales ──────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'gym_id'    => 'required|exists:gyms,id',
            'address'   => 'nullable|string|max:500',
            'phone'     => 'nullable|string|max:20',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch = $this->service->createBranch(
            $request->only(['gym_id', 'name', 'address', 'phone', 'latitude', 'longitude', 'is_active'])
        );

        return response()->json(['message' => 'Sucursal creada correctamente.', 'data' => $branch], 201);
    }

    // ── PUT /gestion/sucursales/{id} ──────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'sometimes|required|string|max:255',
            'address'   => 'nullable|string|max:500',
            'phone'     => 'nullable|string|max:20',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $branch = $this->service->updateBranch(
            $id,
            $request->only(['name', 'address', 'phone', 'latitude', 'longitude', 'is_active'])
        );

        return response()->json(['message' => 'Sucursal actualizada correctamente.', 'data' => $branch]);
    }

    // ── PATCH /gestion/sucursales/{id}/toggle ────────────────────────
    public function toggle(int $id): JsonResponse
    {
        $result    = $this->service->toggleBranch($id);
        $action    = $result['is_active'] ? 'activada' : 'desactivada';

        return response()->json([
            'message'   => "Sucursal {$action} correctamente.",
            'is_active' => $result['is_active'],
        ]);
    }

    // ── DELETE /gestion/sucursales/{id} ──────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteBranch($id);

        return response()->json(['message' => 'Sucursal eliminada correctamente.']);
    }

    // ── POST /gestion/sucursales/{id}/subscriptions ──────────────────
    public function storeSubscription(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan'      => 'required|in:monthly,quarterly,yearly',
            'price'     => 'required|numeric|min:0',
            'starts_at' => 'required|date',
            'ends_at'   => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscription = $this->service->createSubscription($id, $request->all());

        return response()->json(['message' => 'Suscripción registrada correctamente.', 'data' => $subscription], 201);
    }

    // ── PUT /gestion/sucursales/{id}/subscriptions/{subId} ───────────
    public function updateSubscription(Request $request, int $id, int $subId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan'      => 'sometimes|required|in:monthly,quarterly,yearly',
            'price'     => 'sometimes|required|numeric|min:0',
            'starts_at' => 'sometimes|required|date',
            'ends_at'   => 'sometimes|required|date',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscription = $this->service->updateSubscription(
            $id,
            $subId,
            $request->only(['plan', 'price', 'starts_at', 'ends_at', 'is_active'])
        );

        return response()->json(['message' => 'Suscripción actualizada correctamente.', 'data' => $subscription]);
    }

    // ── POST /gestion/sucursales/{id}/subscriptions/{subId}/payments ─
    public function storePayment(Request $request, int $id, int $subId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount'         => 'required|numeric|min:0',
            'status'         => 'required|in:pending,paid,failed,cancelled',
            'payment_method' => 'nullable|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'paid_at'        => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment = $this->service->createPayment($id, $subId, $request->all());

        return response()->json(['message' => 'Pago registrado correctamente.', 'data' => $payment], 201);
    }

    // ── GET /gestion/gyms-list ────────────────────────────────────────
    public function gymsList(): JsonResponse
    {
        return response()->json(['data' => $this->service->getGymsList()]);
    }
}