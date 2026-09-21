<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleCompra extends Model
{
    protected $fillable = [
        'compra_id', 'producto_id', 'cantidad',
        'precio_unitario', 'precio_total', 'cantidad_vendida',
        'lote', 'fecha_vencimiento',
    ];

    protected function casts(): array
    {
        return [
            'cantidad'         => 'integer',
            'cantidad_vendida' => 'integer',
            'precio_unitario'  => 'decimal:2',
            'precio_total'     => 'decimal:2',
            'fecha_vencimiento' => 'date:Y-m-d',
        ];
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
