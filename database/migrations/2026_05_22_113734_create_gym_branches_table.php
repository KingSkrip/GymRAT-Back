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
  Schema::create('gym_branches', function (Blueprint $table) {
    $table->id();

    $table->foreignId('gym_id')
        ->constrained()
        ->onDelete('cascade');

    $table->string('name');

    $table->string('address')->nullable();
    $table->string('phone')->nullable();

    $table->decimal('latitude', 10, 7)->nullable();
    $table->decimal('longitude', 10, 7)->nullable();

    $table->boolean('is_active')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_branches');
    }
};