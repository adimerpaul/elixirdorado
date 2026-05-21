<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sixpack extends Model
{
    use SoftDeletes;

    protected $table = 'sixpacks';

    protected $fillable = [
        'sucursal_id', 'codigo_barras', 'nombre', 'imagen', 'categoria_id',
        'precio_compra', 'precio_venta', 'precio_mayoreo',
        'stock_minimo', 'stock_maximo', 'activo',
    ];

    protected $casts = [
        'precio_compra'  => 'decimal:2',
        'precio_venta'   => 'decimal:2',
        'precio_mayoreo' => 'decimal:2',
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
        return $this->belongsTo(Categoria::class);
    }

    public function componentes(): HasMany
    {
        return $this->hasMany(SixpackComponente::class);
    }
}
