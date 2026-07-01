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
        Schema::create('measurements_user', function (Blueprint $table) {

    $table->id();

    $table->foreignId('assessment_id')
        ->constrained('assessments_user')
        ->cascadeOnDelete();

    $table->decimal('neck',5,2)->nullable();

    $table->decimal('shoulders',5,2)->nullable();

    $table->decimal('chest',5,2)->nullable();

    $table->decimal('left_arm',5,2)->nullable();
    $table->decimal('right_arm',5,2)->nullable();

    $table->decimal('waist',5,2)->nullable();

    $table->decimal('abdomen',5,2)->nullable();

    $table->decimal('hip',5,2)->nullable();

    $table->decimal('left_thigh',5,2)->nullable();
    $table->decimal('right_thigh',5,2)->nullable();

    $table->decimal('left_calf',5,2)->nullable();
    $table->decimal('right_calf',5,2)->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};