<?php
// app/Http/Controllers/ExerciseController.php

namespace App\Http\Controllers\Exercises;

use App\Http\Controllers\Controller;
use App\Http\Requests\Excesises\StoreExerciseRequest;
use App\Http\Requests\Excesises\UpdateExerciseRequest;
use App\Models\Exercise;
use App\Services\Exercises\ExerciseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    public function __construct(protected ExerciseService $exerciseService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'muscle_group', 'equipment', 'difficulty', 'search', 'is_active',
        ]);

        $perPage = (int) $request->input('per_page', 20);

        $exercises = $this->exerciseService->list($filters, $perPage);

        return response()->json($exercises);
    }

    public function store(StoreExerciseRequest $request): JsonResponse
    {
        $exercise = $this->exerciseService->create($request->validated());

        return response()->json($exercise, 201);
    }

    public function show(Exercise $exercise): JsonResponse
    {
        return response()->json($exercise);
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise): JsonResponse
    {
        $exercise = $this->exerciseService->update($exercise, $request->validated());

        return response()->json($exercise);
    }

    public function destroy(Exercise $exercise): JsonResponse
    {
        $this->exerciseService->delete($exercise);

        return response()->json(null, 204);
    }

    public function toggleActive(Exercise $exercise): JsonResponse
    {
        $exercise = $this->exerciseService->toggleActive($exercise);

        return response()->json($exercise);
    }
}