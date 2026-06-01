<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('qr_tokens', function (Blueprint $table) {

            // eliminar basura temporal
            $table->dropColumn([
                'expires_at',
                'is_used',
            ]);

            // nuevo estado
            $table->boolean('is_active')
                ->default(true)
                ->after('token');

            // último uso
            $table->timestamp('last_used_at')
                ->nullable()
                ->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('qr_tokens', function (Blueprint $table) {

            $table->timestamp('expires_at')->nullable();

            $table->boolean('is_used')->default(false);

            $table->dropColumn([
                'is_active',
                'last_used_at',
            ]);
        });
    }
};