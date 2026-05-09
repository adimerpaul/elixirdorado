<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Whisky, Vodka, Ron, Cerveza, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('categorias');
    }
};
