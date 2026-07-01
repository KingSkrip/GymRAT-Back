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
        Schema::create('diets_user', function (Blueprint $table) {

            $table->id();

            $table->foreignId('coach_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');

            $table->text('description')->nullable();

            $table->unsignedSmallInteger('calories')->nullable();

            $table->unsignedSmallInteger('protein')->nullable();

            $table->unsignedSmallInteger('carbs')->nullable();

            $table->unsignedSmallInteger('fat')->nullable();

            $table->unsignedSmallInteger('water')->nullable();

            $table->date('starts_at')->nullable();

            $table->date('ends_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diets_user');
    }
};