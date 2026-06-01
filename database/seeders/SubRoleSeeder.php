<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubRole;

class SubRoleSeeder extends Seeder
{
    public function run(): void
    {
        $subroles = [
            [
                'name' => 'senior_coach',
                'description' => 'Coach con experiencia avanzada'
            ],
            [
                'name' => 'junior_coach',
                'description' => 'Coach en entrenamiento o nivel básico'
            ],
            [
                'name' => 'branch_manager',
                'description' => 'Encargado de sucursal del gym'
            ],
            [
                'name' => 'receptionist',
                'description' => 'Recepción y control de accesos'
            ],
            [
                'name' => 'personal_trainer',
                'description' => 'Entrenador personal'
            ],
        ];

        foreach ($subroles as $subrole) {
            SubRole::updateOrCreate(
                ['name' => $subrole['name']],
                $subrole
            );
        }
    }
}