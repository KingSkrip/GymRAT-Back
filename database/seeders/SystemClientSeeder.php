<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemClient;

class SystemClientSeeder extends Seeder
{
    public function run(): void
    {
        SystemClient::updateOrCreate(
            ['email' => 'demo@gymrat.com'],
            [
                'name' => 'Gym Demo Client',
                'phone' => '0000000000',
                'is_active' => true,
                'subscription_start' => now(),
                'subscription_end' => now()->addMonth()
            ]
        );
    }
}