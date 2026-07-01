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
        Schema::create('skinfolds_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_id')
                ->constrained('assessments_user')
                ->cascadeOnDelete();

            $table->decimal('chest', 5, 2)->nullable();

            $table->decimal('tricep', 5, 2)->nullable();

            $table->decimal('subscapular', 5, 2)->nullable();

            $table->decimal('midaxillary', 5, 2)->nullable();

            $table->decimal('suprailiac', 5, 2)->nullable();

            $table->decimal('abdomen', 5, 2)->nullable();

            $table->decimal('thigh', 5, 2)->nullable();

            $table->decimal('calf', 5, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skinfolds_user');
    }
};