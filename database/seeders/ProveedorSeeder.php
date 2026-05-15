<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Sucursal::all() as $sucursal) {
            Proveedor::firstOrCreate(
                ['sucursal_id' => $sucursal->id, 'nombre' => 'Proveedor General'],
                ['activo' => true]
            );
        }
    }
}
