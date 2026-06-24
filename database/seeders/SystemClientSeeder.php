<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemClient;

class SystemClientSeeder extends Seeder
{
    public function run(): void
    {
        SystemClient::updateOrCreate(
            [
                'user_id' => 3,
            ],
            [
                'phone' => '0000000000',
                'is_active' => true,
                'subscription_start' => now(),
                'subscription_end' => now()->addMonth(),
            ]
        );
    }
}