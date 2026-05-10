<?php

namespace App\Console\Commands;

use App\Models\Sucursal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrarTenants extends Command
{
    protected $signature   = 'tenants:migrate';
    protected $description = 'Corre las migraciones tenant pendientes en todas las sucursales y corrige columnas faltantes';

    public function handle(): void
    {
        $sucursales = Sucursal::withTrashed()->get();

        foreach ($sucursales as $s) {
            $this->info("Procesando: {$s->nombre} ({$s->base_datos})");

            config(['database.connections.tenant.database' => $s->base_datos]);
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Run pending migrations first
            Artisan::call('migrate', [
                '--path'     => 'database/migrations/tenant',
                '--database' => 'tenant',
                '--force'    => true,
            ]);

            // Ensure new columns exist regardless of migration tracking
            $this->fixColumns();

            $cols = Schema::connection('tenant')->getColumnListing('productos');
            $this->line("  ✓ Columnas: " . implode(', ', $cols));
        }

        $this->info('Listo.');
    }

    private function fixColumns(): void
    {
        $cols = Schema::connection('tenant')->getColumnListing('productos');

        if (!in_array('precio_mayoreo', $cols)) {
            DB::connection('tenant')->statement(
                'ALTER TABLE `productos` ADD COLUMN `precio_mayoreo` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `precio_venta`'
            );
            $this->line("  + Columna precio_mayoreo agregada");
        }

        if (!in_array('stock_maximo', $cols)) {
            DB::connection('tenant')->statement(
                'ALTER TABLE `productos` ADD COLUMN `stock_maximo` INT NOT NULL DEFAULT 100 AFTER `stock_minimo`'
            );
            $this->line("  + Columna stock_maximo agregada");
        }

        if (!in_array('deleted_at', $cols)) {
            DB::connection('tenant')->statement(
                'ALTER TABLE `productos` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL'
            );
            $this->line("  + Columna deleted_at agregada");
        }
    }
}
