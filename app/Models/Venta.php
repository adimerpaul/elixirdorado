<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $connection = 'tenant';
    protected $table      = 'ventas';

    protected $fillable = [
        'folio', 'usuario_id', 'cliente_id',
        'total', 'metodo_pago', 'estado', 'notas',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

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
