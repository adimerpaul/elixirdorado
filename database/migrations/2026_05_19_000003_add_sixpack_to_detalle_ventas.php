<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make producto_id nullable so sixpack-only rows can have producto_id = NULL
        DB::statement('ALTER TABLE detalle_ventas MODIFY COLUMN producto_id BIGINT UNSIGNED NULL');

        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('sixpack_id')->nullable()->after('producto_id');
            $table->foreign('sixpack_id')->references('id')->on('sixpacks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropForeign(['sixpack_id']);
            $table->dropColumn('sixpack_id');
        });
        DB::statement('ALTER TABLE detalle_ventas MODIFY COLUMN producto_id BIGINT UNSIGNED NOT NULL');
    }
};
