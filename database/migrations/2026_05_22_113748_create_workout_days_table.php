<?php
// database/migrations/xxxx_xx_xx_create_workout_days_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workout_id')
                ->constrained('workouts')
                ->cascadeOnDelete();

            // "Día 1", "Día 2"... solo para mostrar/ordenar
            $table->unsignedTinyInteger('day_number');

            // Nombre libre del día, ej. "Espalda lumbar y hombro"
            $table->string('label')->nullable();

            // En qué días de la semana cae este bloque, ej. [1,3,6] = Lun/Mié/Sáb
            // 1 = Lunes ... 7 = Domingo (o el criterio que uses)
            $table->json('weekdays')->nullable();

            // Notas generales del día (ej. "Al finalizar 10 min elíptica")
            $table->text('notes')->nullable();

            $table->unsignedSmallInteger('order')->default(0);

            $table->timestamps();

            $table->unique(['workout_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_days');
    }
};