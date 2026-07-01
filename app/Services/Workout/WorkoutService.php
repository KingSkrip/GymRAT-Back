<?php
// app/Services/WorkoutService.php

namespace App\Services\Workout;

use App\Models\Workout;
use App\Models\WorkoutDay;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WorkoutService
{
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Workout::query()->with('days.exercises.exercise');

        if (!empty($filters['coach_id'])) {
            $query->where('coach_id', $filters['coach_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function find(int $id): Workout
    {
        return Workout::with('days.exercises.exercise')->findOrFail($id);
    }

    /**
     * Crea la rutina completa: workout + days + exercises, en una sola transacción.
     */
    public function create(array $data): Workout
    {
        return DB::transaction(function () use ($data) {
            $days = $data['days'];
            unset($data['days']);

            $workout = Workout::create($data);

            $this->syncDays($workout, $days);

            return $workout->fresh(['days.exercises.exercise']);
        });
    }

    /**
     * Actualiza el workout. Si viene `days`, reemplaza TODO el árbol de días/ejercicios
     * (borra los que ya no vienen, actualiza los que traen `id`, crea los nuevos).
     */
    public function update(Workout $workout, array $data): Workout
    {
        return DB::transaction(function () use ($workout, $data) {
            $days = $data['days'] ?? null;
            unset($data['days']);

            $workout->update($data);

            if ($days !== null) {
                $this->syncDays($workout, $days);
            }

            return $workout->fresh(['days.exercises.exercise']);
        });
    }

    public function delete(Workout $workout): void
    {
        // Los days y exercises se van solos por cascadeOnDelete()
        $workout->delete();
    }

    /**
     * Reemplaza el árbol completo de días/ejercicios de un workout.
     * Estrategia simple: borra días/ejercicios que no vienen en el payload,
     * actualiza los que traen `id`, crea los que no.
     */
    protected function syncDays(Workout $workout, array $days): void
    {
        $incomingDayIds = collect($days)->pluck('id')->filter()->all();

        // Borra días que ya no están en el payload
        $workout->days()
            ->when(!empty($incomingDayIds), fn ($q) => $q->whereNotIn('id', $incomingDayIds))
            ->when(empty($incomingDayIds), fn ($q) => $q) // si no viene ningún id, se limpian todos abajo
            ->get()
            ->each(function (WorkoutDay $day) use ($incomingDayIds) {
                if (!in_array($day->id, $incomingDayIds, true)) {
                    $day->delete(); // cascade borra sus exercises
                }
            });

        foreach ($days as $index => $dayData) {
            $exercises = $dayData['exercises'];
            unset($dayData['exercises']);

            $dayData['order'] = $dayData['order'] ?? $index;

            if (!empty($dayData['id'])) {
                $day = $workout->days()->findOrFail($dayData['id']);
                $day->update($dayData);
            } else {
                unset($dayData['id']);
                $day = $workout->days()->create($dayData);
            }

            $this->syncExercises($day, $exercises);
        }
    }

    protected function syncExercises(WorkoutDay $day, array $exercises): void
    {
        $incomingIds = collect($exercises)->pluck('id')->filter()->all();

        $day->exercises()
            ->get()
            ->each(function ($exercise) use ($incomingIds) {
                if (!in_array($exercise->id, $incomingIds, true)) {
                    $exercise->delete();
                }
            });

        foreach ($exercises as $index => $exerciseData) {
            $exerciseData['order'] = $exerciseData['order'] ?? $index;

            if (!empty($exerciseData['id'])) {
                $item = $day->exercises()->findOrFail($exerciseData['id']);
                $item->update($exerciseData);
            } else {
                unset($exerciseData['id']);
                $day->exercises()->create($exerciseData);
            }
        }
    }
}