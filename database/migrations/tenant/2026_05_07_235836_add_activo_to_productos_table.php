<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('productos', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('stock_minimo');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('productos', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
