<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Administrador (ve todo)
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@elixirdorado.com',
            'password' => Hash::make('12345678'),
            'rol' => 'super_admin',
        ]);
        
        // Buscar sucursales existentes
        $sucursalCentro = Sucursal::where('slug', 'centro')->first();
        $sucursalSur = Sucursal::where('slug', 'sur')->first();
        
        // Administrador de sucursal centro
        if ($sucursalCentro) {
            User::create([
                'name' => 'Admin Centro',
                'email' => 'admin@centro.com',
                'password' => Hash::make('12345678'),
                'rol' => 'admin',
                'sucursal_id' => $sucursalCentro->id,
            ]);
            
            // Cajero de sucursal centro
            User::create([
                'name' => 'Cajero Centro',
                'email' => 'cajero@centro.com',
                'password' => Hash::make('12345678'),
                'rol' => 'cajero',
                'sucursal_id' => $sucursalCentro->id,
            ]);
        }
        
        // Administrador de sucursal sur
        if ($sucursalSur) {
            User::create([
                'name' => 'Admin Sur',
                'email' => 'admin@sur.com',
                'password' => Hash::make('12345678'),
                'rol' => 'admin',
                'sucursal_id' => $sucursalSur->id,
            ]);
        }
    }
}