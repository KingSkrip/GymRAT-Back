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
       Schema::create('users', function (Blueprint $table) {
    $table->id();

    $table->foreignId('gym_id')
        ->nullable()
        ->constrained()
        ->onDelete('cascade');

    $table->foreignId('gymbranch_id')
        ->nullable()
        ->constrained('gym_branches')
        ->onDelete('cascade');

    $table->string('name')->nullable();
    $table->string('phone')->unique()->nullable();
    $table->string('email')->unique()->nullable();
    $table->string('password')->nullable();

    $table->boolean('is_active')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};