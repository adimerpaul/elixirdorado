<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

class Sucursal extends Model
{
    protected $connection = 'central';
    
    protected $table = 'sucursales';
    
    protected $fillable = [
        'nombre', 'slug', 'base_datos', 'direccion', 'telefono', 'email', 'activa'
    ];
    
    // Método para crear una nueva sucursal
    public static function crearSucursal($datos)
    {
        // Validar slug: solo minúsculas, números, guiones bajos y guiones (evita SQL injection en CREATE DATABASE)
        if (!isset($datos['slug']) || !preg_match('/^[a-z0-9_-]+$/', $datos['slug'])) {
            throw new \InvalidArgumentException('Slug inválido. Solo se permiten letras minúsculas, números, "_" y "-".');
        }

        // Password de admin obligatorio (mínimo 8 caracteres) — sin fallback inseguro
        if (empty($datos['admin_password']) || strlen($datos['admin_password']) < 8) {
            throw new \InvalidArgumentException('Se requiere una contraseña de administrador de al menos 8 caracteres.');
        }

        $nombreBD = 'elixir_' . $datos['slug'];

        // 1. Crear la base de datos (slug ya validado arriba; los backticks aíslan el identificador)
        DB::connection('central')->statement("CREATE DATABASE IF NOT EXISTS `{$nombreBD}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // 2. Crear el registro en la tabla central
        $sucursal = self::create([
            'nombre'     => $datos['nombre'],
            'slug'       => $datos['slug'],
            'base_datos' => $nombreBD,
            'direccion'  => $datos['direccion'] ?? null,
            'telefono'   => $datos['telefono'] ?? null,
            'email'      => $datos['email']    ?? null,
        ]);

        // 3. Configurar temporalmente la conexión tenant
        config(['database.connections.tenant.database' => $nombreBD]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // 4. Ejecutar TODAS las migraciones tenant (incluye clientes, etc.)
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);

        // 5. Crear usuario administrador en central.users (para que pueda iniciar sesión)
        $adminEmail = $datos['admin_email'] ?? 'admin@' . $datos['slug'] . '.local';
        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name'        => $datos['admin_nombre'] ?? 'Administrador',
                'email'       => $adminEmail,
                'password'    => bcrypt($datos['admin_password']),
                'rol'         => 'admin',
                'sucursal_id' => $sucursal->id,
            ]);
        }

        return $sucursal;
    }
}