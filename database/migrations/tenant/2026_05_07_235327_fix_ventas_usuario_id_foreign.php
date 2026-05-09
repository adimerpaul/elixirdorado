<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('ventas', function (Blueprint $table) {
            // La FK apunta a tenant.usuarios pero auth()->id() viene de central.users
            // Esto causa error cuando el ID del usuario central no existe en tenant.usuarios
            if ($this->foreignKeyExists('ventas', 'ventas_usuario_id_foreign')) {
                $table->dropForeign('ventas_usuario_id_foreign');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('ventas', function (Blueprint $table) {
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $results = DB::connection('tenant')->select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $name]);
        return count($results) > 0;
    }
};
