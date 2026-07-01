<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('coach_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // Objetivo de la rutina
            $table->enum('goal', [
                'Fuerza',
                'Hipertrofia',
                'Pérdida de grasa',
                'Acondicionamiento físico',
                'Rehabilitación',
                'Personalizado',
            ])->default('Personalizado');

            // Nivel del cliente
            $table->enum('level', [
                'Principiante',
                'Intermedio',
                'Avanzado'
            ])->default('Principiante');

            // Días por semana
            $table->unsignedTinyInteger('days_per_week')->nullable();

            // Duración estimada en minutos
            $table->unsignedSmallInteger('estimated_duration')->nullable();

            // Vigencia
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();

            // Rutina activa
            $table->boolean('is_active')->default(true);

            $table->json('exercises')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};