<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionInitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'stylbandeira@gmail.com'],
            [
                'name' => 'Administrador',
                'type' => User::TYPE_ADMIN,
                'password' => Hash::make(env('INITIAL_ADMIN_PASSWORD')),
                'status' => 'active',
            ]
        );
    }
}
