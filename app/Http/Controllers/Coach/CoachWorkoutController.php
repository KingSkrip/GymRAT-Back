<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workout\StoreWorkoutRequest;
use App\Http\Requests\Workout\UpdateWorkoutRequest;
use App\Http\Resources\Workout\WorkoutResource;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workout;
use App\Services\Workout\WorkoutService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CoachWorkoutController extends Controller
{
    public function __construct(protected WorkoutService $workoutService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['coach_id', 'user_id', 'is_active']);
        $perPage = (int) $request->input('per_page', 20);
        $paginated = $this->workoutService->list($filters, $perPage);

        return response()->json([
            'data' => WorkoutResource::collection($paginated->items()),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    public function store(StoreWorkoutRequest $request): JsonResponse
    {
        $workout = $this->workoutService->create($request->validated());
        return response()->json(new WorkoutResource($workout), 201);
    }

    public function show(Workout $workout): JsonResponse
    {
        return response()->json(new WorkoutResource($workout->load('days.exercises.exercise')));
    }

    public function update(UpdateWorkoutRequest $request, Workout $workout): JsonResponse
    {
        $workout = $this->workoutService->update($workout, $request->validated());
        return response()->json(new WorkoutResource($workout));
    }
    
    public function destroy(Workout $workout): JsonResponse
    {
        $this->workoutService->delete($workout);

        return response()->json(null, 204);
    }
}