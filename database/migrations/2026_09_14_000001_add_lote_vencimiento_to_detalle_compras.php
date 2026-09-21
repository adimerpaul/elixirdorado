<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->string('lote', 100)->nullable()->after('precio_total');
            $table->date('fecha_vencimiento')->nullable()->after('lote');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->dropColumn(['lote', 'fecha_vencimiento']);
        });
    }
};
