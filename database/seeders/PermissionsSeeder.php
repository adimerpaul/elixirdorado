<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['dashboard', 'sucursales', 'usuarios', 'configuracion'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $modules = [
            'productos',
            'ventas.nueva',
            'ventas.historial',
            'compras.nueva',
            'compras.historial',
            'proveedores',
            'stock.minimo',
            'stock.maximo',
        ];

        Sucursal::each(function ($s) use ($modules) {
            Permission::firstOrCreate(['name' => "sucursal.{$s->id}", 'guard_name' => 'web']);
            foreach ($modules as $module) {
                Permission::firstOrCreate(['name' => "sucursal.{$s->id}.{$module}", 'guard_name' => 'web']);
            }
        });
    }
}
