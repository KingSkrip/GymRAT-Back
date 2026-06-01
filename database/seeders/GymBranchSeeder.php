<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gym;
use App\Models\GymBranch;

class GymBranchSeeder extends Seeder
{
    public function run(): void
    {
        $gym = Gym::first();

        GymBranch::updateOrCreate(
            ['name' => 'Sucursal Centro'],
            [
                'gym_id' => $gym?->id,
                'address' => 'Centro Histórico',
                'phone' => '0000000000',
                'latitude' => 19.2826,
                'longitude' => -99.6557,
                'is_active' => true
            ]
        );
    }
}