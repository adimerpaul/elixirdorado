<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'sucursal_id', 'codigo_barras', 'nombre', 'descripcion', 'imagen', 'categoria_id',
        'precio_compra', 'precio_venta', 'precio_mayoreo',
        'stock_actual', 'stock_minimo', 'stock_maximo', 'activo',
    ];

    protected $casts = [
        'precio_compra'  => 'decimal:2',
        'precio_venta'   => 'decimal:2',
        'precio_mayoreo' => 'decimal:2',
        'stock_actual'   => 'integer',
        'stock_minimo'   => 'integer',
        'stock_maximo'   => 'integer',
        'activo'         => 'boolean',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }

    public function scopeBajoStock($q)
    {
        return $q->whereColumn('stock_actual', '<=', 'stock_minimo');
    }
}
