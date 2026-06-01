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
        Schema::create('client_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_client_id')->constrained()->onDelete('cascade');

            $table->enum('plan', ['monthly', 'yearly'])->nullable();
            $table->decimal('price', 10, 2)->nullable()->nullable();

            $table->date('starts_at');
            $table->date('ends_at')->nullable();

            $table->boolean('is_active')->default(true)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_subscriptions');
    }
};
