<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->unsignedBigInteger('usuario_id'); // ID del central.users, sin FK cross-db
            $table->decimal('subtotal', 10, 2);
            $table->decimal('iva', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('metodo_pago'); // efectivo, tarjeta, transferencia
            $table->string('estado')->default('completada');
            $table->timestamp('fecha_venta')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('ventas');
    }
};
