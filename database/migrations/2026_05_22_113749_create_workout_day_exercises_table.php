<?php
// database/migrations/xxxx_xx_xx_create_workout_day_exercises_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_day_exercises', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workout_day_id')
                ->constrained('workout_days')
                ->cascadeOnDelete();

            // Opcional: referencia al catálogo. Null si es un ejercicio libre/custom.
            $table->foreignId('exercise_id')
                ->nullable()
                ->constrained('exercises')
                ->nullOnDelete();

            // Nombre a mostrar (denormalizado). Si exercise_id existe, se copia
            // del catálogo al agregarlo; si no, el coach lo escribe a mano.
            $table->string('name');

            $table->unsignedTinyInteger('sets')->nullable();
            $table->string('reps')->nullable(); // string porque a veces es "10-12" o "20"
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->unsignedInteger('duration_sec')->nullable();
            $table->unsignedInteger('distance_m')->nullable();

            // Texto libre tipo "AL FALLO", "BI SERIE", "AUMENTANDO PESO CADA SERIE"
            $table->string('note')->nullable();

            $table->unsignedSmallInteger('order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_day_exercises');
    }
};