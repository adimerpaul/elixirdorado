<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $connection = 'tenant';
    protected $table      = 'clientes';

    protected $fillable = [
        'nombre', 'telefono', 'email', 'direccion',
        'limite_credito', 'saldo_credito',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'saldo_credito'  => 'decimal:2',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }
}
