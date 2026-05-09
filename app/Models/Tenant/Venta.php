<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $connection = 'tenant';
    protected $table = 'ventas';

    protected $fillable = [
        'folio', 'usuario_id', 'subtotal', 'iva', 'total',
        'metodo_pago', 'estado', 'fecha_venta',
    ];

    protected $casts = [
        'subtotal'    => 'decimal:2',
        'iva'         => 'decimal:2',
        'total'       => 'decimal:2',
        'fecha_venta' => 'datetime',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function scopeNoCanceladas($q)
    {
        return $q->where('estado', '!=', 'cancelada');
    }
}
