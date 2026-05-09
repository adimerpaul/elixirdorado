<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class UsuarioTenant extends Model
{
    protected $connection = 'tenant';
    protected $table = 'usuarios';

    protected $fillable = ['name', 'email', 'password', 'rol'];

    protected $hidden = ['password'];
}
