<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'sucursal_id', 'nombre', 'telefono', 'email', 'rfc_nit', 'direccion',
        'limite_credito', 'saldo_credito', 'activo',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'saldo_credito'  => 'decimal:2',
        'activo'         => 'boolean',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }
}
