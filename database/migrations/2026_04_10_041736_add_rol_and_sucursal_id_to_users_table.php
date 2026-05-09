<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'rol')) {
                $table->enum('rol', ['super_admin', 'admin', 'cajero'])->default('cajero');
            }
            if (!Schema::hasColumn('users', 'sucursal_id')) {
                $table->foreignId('sucursal_id')->nullable()->constrained('sucursales');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rol', 'sucursal_id']);
        });
    }
};
