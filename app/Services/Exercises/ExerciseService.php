<?php
// app/Services/ExerciseService.php

namespace App\Services\Exercises;

use App\Models\Exercise;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExerciseService
{
    /**
     * Lista paginada con filtros opcionales.
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Exercise::query();

        if (!empty($filters['muscle_group'])) {
            $query->muscleGroup($filters['muscle_group']);
        }

        if (!empty($filters['equipment'])) {
            $query->equipment($filters['equipment']);
        }

        if (!empty($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        } else {
            $query->active(); // por defecto solo activos
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function find(int $id): Exercise
    {
        return Exercise::findOrFail($id);
    }

    public function findBySlug(string $slug): Exercise
    {
        return Exercise::where('slug', $slug)->firstOrFail();
    }

    public function create(array $data): Exercise
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);

            return Exercise::create($data);
        });
    }

    public function update(Exercise $exercise, array $data): Exercise
    {
        return DB::transaction(function () use ($exercise, $data) {
            if (!empty($data['name']) && $data['name'] !== $exercise->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $exercise->id);
            }

            $exercise->update($data);

            return $exercise->fresh();
        });
    }

    public function delete(Exercise $exercise): void
    {
        $exercise->delete();
    }

    /**
     * Soft toggle: en vez de borrar, desactiva.
     */
    public function toggleActive(Exercise $exercise): Exercise
    {
        $exercise->update(['is_active' => !$exercise->is_active]);

        return $exercise->fresh();
    }

    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $i = 1;

        while (
            Exercise::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }
}