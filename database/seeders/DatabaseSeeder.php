<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@elixir.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
                'rol'      => 'super_admin',
            ]
        );

        $this->call(SucursalSeeder::class);
    }
}
