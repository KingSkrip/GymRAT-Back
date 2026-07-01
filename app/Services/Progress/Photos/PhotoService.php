<?php

namespace App\Services\Progress\Photos;

use App\Models\ProgressPhotoUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PhotoService
{
    /**
     * Obtener fotos de una evaluación
     */
    public function show(int $assessmentId): ?ProgressPhotoUser
    {
        return ProgressPhotoUser::where('assessment_id', $assessmentId)
            ->first();
    }

    /**
     * Guardar o actualizar fotos
     */
    public function store(int $assessmentId, Request $request): ProgressPhotoUser
    {
        return DB::transaction(function () use ($assessmentId, $request) {

            $photo = ProgressPhotoUser::firstOrCreate([
                'assessment_id' => $assessmentId
            ]);

            $this->upload($request, $photo, 'front');
            $this->upload($request, $photo, 'back');
            $this->upload($request, $photo, 'left_side');
            $this->upload($request, $photo, 'right_side');

            $photo->save();

            return $photo->fresh();
        });
    }

    /**
     * Eliminar una foto específica
     */
    public function destroy(int $assessmentId, string $type): void
    {
        $allowed = [
            'front',
            'back',
            'left_side',
            'right_side'
        ];

        if (! in_array($type, $allowed)) {
            abort(422, 'Tipo de fotografía inválido.');
        }

        $photo = ProgressPhotoUser::where(
            'assessment_id',
            $assessmentId
        )->firstOrFail();

        if ($photo->$type) {

            Storage::disk('public')->delete($photo->$type);

            $photo->$type = null;

            $photo->save();
        }
    }

    /**
     * Subir una fotografía
     */
    private function upload(
        Request $request,
        ProgressPhotoUser $photo,
        string $field
    ): void {
        if (!$request->hasFile($field)) {
            return;
        }

        if ($photo->$field) {
            Storage::disk('public')->delete($photo->$field);
        }

        $photo->$field = $request
            ->file($field)
            ->store('progress/photos', 'public');
    }
}
