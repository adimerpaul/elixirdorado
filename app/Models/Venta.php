<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'sucursal_id', 'folio', 'usuario_id', 'cliente_id',
        'subtotal', 'iva', 'total', 'metodo_pago', 'estado', 'fecha_venta',
    ];

    protected $casts = [
        'subtotal'     => 'decimal:2',
        'iva'          => 'decimal:2',
        'total'        => 'decimal:2',
        'fecha_venta'  => 'datetime',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function scopeNoCanceladas($q)
    {
        return $q->where('estado', '!=', 'cancelada');
    }
}
