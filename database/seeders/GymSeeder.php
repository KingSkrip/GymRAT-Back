<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gym;
use App\Models\SystemClient;

class GymSeeder extends Seeder
{
    public function run(): void
    {
        $client = SystemClient::first();

        Gym::updateOrCreate(
            ['name' => 'Gym Demo'],
            [
                'system_client_id' => $client?->id,
                'address' => 'Centro, Toluca',
                'phone' => '0000000000',
                'is_active' => true
            ]
        );
    }
}