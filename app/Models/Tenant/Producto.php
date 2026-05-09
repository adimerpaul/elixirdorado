<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'productos';

    protected $fillable = [
        'codigo_barras', 'nombre', 'descripcion', 'categoria_id',
        'precio_compra', 'precio_venta', 'stock_actual', 'stock_minimo', 'imagen',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta'  => 'decimal:2',
        'stock_actual'  => 'integer',
        'stock_minimo'  => 'integer',
    ];

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
