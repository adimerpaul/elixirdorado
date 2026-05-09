<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('tenant')->hasColumn('productos', 'imagen')) {
            Schema::connection('tenant')->table('productos', function (Blueprint $table) {
                $table->string('imagen')->nullable()->after('descripcion');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('productos', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }
};
