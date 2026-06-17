<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_branch_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gym_branch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('plan', [
                'monthly',
                'quarterly',
                'yearly'
            ])->default('monthly');

            $table->decimal('price', 10, 2);

            $table->date('starts_at');

            $table->date('ends_at');

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_branch_subscriptions');
    }
};