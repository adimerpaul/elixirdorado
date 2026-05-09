<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Sucursal;

class MigrateTenants extends Command
{
    protected $signature = 'migrate:tenants {--fresh : Drop all tables and re-run migrations}';
    protected $description = 'Ejecuta las migraciones en todas las bases de datos de sucursales (tenants)';

    public function handle()
    {
        $sucursales = Sucursal::where('activa', true)->get();

        if ($sucursales->isEmpty()) {
            $this->warn('⚠️  No hay sucursales activas registradas.');
            $this->info('Primero crea una sucursal desde /admin');
            return;
        }

        $this->info("🏪 Migrando " . $sucursales->count() . " sucursal(es)...\n");

        foreach ($sucursales as $sucursal) {
            $this->info("📦 Sucursal: {$sucursal->nombre} → {$sucursal->base_datos}");

            try {
                // Apuntar la conexión tenant a la base de datos de esta sucursal
                config(['database.connections.tenant.database' => $sucursal->base_datos]);
                DB::purge('tenant');
                DB::reconnect('tenant');

                if ($this->option('fresh')) {
                    Artisan::call('migrate:fresh', [
                        '--database' => 'tenant',
                        '--path'     => 'database/migrations/tenant',
                        '--force'    => true,
                    ]);
                } else {
                    Artisan::call('migrate', [
                        '--database' => 'tenant',
                        '--path'     => 'database/migrations/tenant',
                        '--force'    => true,
                    ]);
                }

                $output = Artisan::output();
                // Mostrar si hubo migraciones nuevas
                if (str_contains($output, 'migrated') || str_contains($output, 'Migrating')) {
                    $this->line("   " . trim($output));
                } else {
                    $this->line("   ✅ Sin cambios pendientes");
                }

            } catch (\Exception $e) {
                $this->error("   ❌ Error en {$sucursal->nombre}: " . $e->getMessage());
            }

            $this->line('');
        }

        $this->info("✅ Proceso completado.");
    }
}
