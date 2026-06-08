<?php

use App\Models\Sucursal;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $globalPerms = ['dashboard', 'sucursales', 'usuarios', 'configuracion'];

    private array $modules = [
        'productos',
        'ventas.nueva',
        'ventas.historial',
        'compras.nueva',
        'compras.historial',
        'proveedores',
        'stock.minimo',
        'stock.maximo',
    ];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->globalPerms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Sucursal::each(function ($s) {
            Permission::firstOrCreate(['name' => "sucursal.{$s->id}", 'guard_name' => 'web']);
            foreach ($this->modules as $module) {
                Permission::firstOrCreate(['name' => "sucursal.{$s->id}.{$module}", 'guard_name' => 'web']);
            }
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Sucursal::each(function ($s) {
            foreach ($this->modules as $module) {
                Permission::where('name', "sucursal.{$s->id}.{$module}")->delete();
            }
        });

        Permission::whereIn('name', ['configuracion'])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
