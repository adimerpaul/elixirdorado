<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'clientes';

    protected $fillable = [
        'nombre', 'telefono', 'email', 'rfc_nit', 'direccion',
        'limite_credito', 'saldo_credito', 'activo',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'saldo_credito'  => 'decimal:2',
        'activo'         => 'boolean',
    ];
}
