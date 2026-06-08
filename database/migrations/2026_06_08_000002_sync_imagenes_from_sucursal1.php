<?php

use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $sucursal1 = Sucursal::find(1);
        if (!$sucursal1) return;

        $productosConImagen = Producto::where('sucursal_id', 1)
            ->whereNotNull('imagen')
            ->get();

        foreach ($productosConImagen as $producto) {
            $query = Producto::where('sucursal_id', '!=', 1)
                ->whereNull('imagen');

            if (!empty($producto->codigo_barras)) {
                $query->where('codigo_barras', $producto->codigo_barras);
            } else {
                $query->whereRaw('LOWER(nombre) = LOWER(?)', [$producto->nombre]);
            }

            $query->update(['imagen' => $producto->imagen]);
        }
    }

    public function down(): void {}
};
