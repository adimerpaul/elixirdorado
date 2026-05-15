<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            if (!Schema::hasColumn('compras', 'proveedor_id')) {
                $table->foreignId('proveedor_id')->nullable()->after('user_id')->constrained('proveedores')->nullOnDelete();
            } else {
                $table->foreign('proveedor_id')->references('id')->on('proveedores')->nullOnDelete();
            }
        });

        if (Schema::hasColumn('compras', 'proveedor')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->dropColumn('proveedor');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->string('proveedor', 150)->nullable()->after('user_id');
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
            $table->dropColumn('proveedor_id');
        });
    }
};
