<?php

use App\Models\Sucursal;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Sucursal::each(function ($s) {
            Permission::firstOrCreate(['name' => "sucursal.{$s->id}.ventas.totales", 'guard_name' => 'web']);
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::where('name', 'like', 'sucursal.%.ventas.totales')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
