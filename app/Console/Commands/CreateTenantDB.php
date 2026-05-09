<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Sucursal;

class CreateTenantDB extends Command
{
    protected $signature = 'tenant:create {slug : El slug de la sucursal}';
    protected $description = 'Crea la base de datos y ejecuta migraciones para una sucursal específica';

    public function handle()
    {
        $slug = $this->argument('slug');

        // Validar slug para evitar inyección en CREATE DATABASE
        if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
            $this->error("❌ Slug inválido. Solo se permiten letras minúsculas, números, '_' y '-'.");
            return 1;
        }

        $sucursal = Sucursal::where('slug', $slug)->first();

        if (!$sucursal) {
            $this->error("❌ No se encontró una sucursal con slug: {$slug}");
            return 1;
        }

        $db = $sucursal->base_datos;

        // Validar nombre de BD persistido también (defensa en profundidad)
        if (!preg_match('/^[A-Za-z0-9_]+$/', $db)) {
            $this->error("❌ Nombre de base de datos inválido en el registro de la sucursal.");
            return 1;
        }
        $this->info("🏪 Configurando sucursal: {$sucursal->nombre}");
        $this->info("   Base de datos: {$db}");

        // Crear la base de datos si no existe
        try {
            DB::connection('central')->statement("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("   ✅ Base de datos creada/verificada");
        } catch (\Exception $e) {
            $this->error("   ❌ Error creando BD: " . $e->getMessage());
            return 1;
        }

        // Ejecutar migraciones
        try {
            config(['database.connections.tenant.database' => $db]);
            DB::purge('tenant');
            DB::reconnect('tenant');

            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path'     => 'database/migrations/tenant',
                '--force'    => true,
            ]);

            $this->info("   ✅ Migraciones ejecutadas:");
            $this->line("   " . trim(Artisan::output()));
        } catch (\Exception $e) {
            $this->error("   ❌ Error en migraciones: " . $e->getMessage());
            return 1;
        }

        $this->info("\n✅ Sucursal '{$sucursal->nombre}' configurada correctamente.");
        $this->info("   URL: http://localhost:8000/{$slug}");
        return 0;
    }
}
