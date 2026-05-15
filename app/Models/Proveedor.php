<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = ['sucursal_id', 'nombre', 'telefono', 'email', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }
}
