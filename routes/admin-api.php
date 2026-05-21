<?php

use App\Http\Controllers\Api\Admin\ClienteController;
use App\Http\Controllers\Api\Admin\CompraController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ProductoController;
use App\Http\Controllers\Api\Admin\ProveedorController;
use App\Http\Controllers\Api\Admin\SucursalController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::get('configuracion', function () {
    return response()->json(\App\Models\Configuracion::todos());
});

Route::post('configuracion', function () {
    $datos = request()->validate([
        'whatsapp'       => 'nullable|string|max:20',
        'nombre_negocio' => 'nullable|string|max:100',
    ]);
    foreach ($datos as $clave => $valor) {
        \App\Models\Configuracion::set($clave, $valor);
    }
    return response()->json(\App\Models\Configuracion::todos());
});

Route::apiResource('sucursales', SucursalController::class)
    ->parameters(['sucursales' => 'sucursal']);
Route::patch('sucursales/{sucursal}/toggle', [SucursalController::class, 'toggle']);

Route::apiResource('users', UserController::class);

Route::prefix('sucursales/{sucursal}')->group(function () {
    Route::get('productos',                    [ProductoController::class, 'index']);
    Route::get('productos/stock-alertas',      [ProductoController::class, 'stockAlertas']);
    Route::get('productos/export/excel',       [ProductoController::class, 'exportExcel']);
    Route::get('productos/export/pdf',         [ProductoController::class, 'exportPdf']);
    Route::get('productos/{id}/historial',     [ProductoController::class, 'historial']);
    Route::post('productos',         [ProductoController::class, 'store']);
    Route::post('productos/{id}',    [ProductoController::class, 'update']);
    Route::delete('productos/{id}',  [ProductoController::class, 'destroy']);

    Route::get('ventas',                  [VentaController::class, 'index']);
    Route::post('ventas',                 [VentaController::class, 'store']);
    Route::patch('ventas/{id}/cancelar',  [VentaController::class, 'cancelar']);

    Route::get('compras',               [CompraController::class, 'index']);
    Route::post('compras',              [CompraController::class, 'store']);
    Route::patch('compras/{id}/anular', [CompraController::class, 'anular']);

    Route::get('clientes',           [ClienteController::class, 'index']);
    Route::post('clientes',          [ClienteController::class, 'store']);

    Route::get('proveedores',                    [ProveedorController::class, 'index']);
    Route::post('proveedores',                   [ProveedorController::class, 'store']);
    Route::put('proveedores/{proveedor}',        [ProveedorController::class, 'update']);
    Route::delete('proveedores/{proveedor}',     [ProveedorController::class, 'destroy']);

    Route::get('sixpacks', function ($sucursal) {
        $sixpacks = \Illuminate\Support\Facades\DB::table('sixpacks')
            ->where('sucursal_id', $sucursal)
            ->whereNull('deleted_at')
            ->orderBy('nombre')
            ->get()
            ->map(function ($sp) {
                $sp->componentes = \Illuminate\Support\Facades\DB::table('sixpack_componentes')
                    ->join('productos', 'productos.id', '=', 'sixpack_componentes.producto_id')
                    ->where('sixpack_componentes.sixpack_id', $sp->id)
                    ->select('sixpack_componentes.*', 'productos.nombre as producto_nombre', 'productos.stock_actual')
                    ->get();
                return $sp;
            });
        return response()->json(['sixpacks' => $sixpacks]);
    });

    Route::post('sixpacks', function ($sucursal) {
        $datos = request()->validate([
            'nombre'          => 'required|string|max:255',
            'codigo_barras'   => 'nullable|string|max:100',
            'categoria_id'    => 'nullable|integer',
            'precio_compra'   => 'required|numeric|min:0',
            'precio_venta'    => 'required|numeric|min:0.01',
            'precio_mayoreo'  => 'nullable|numeric|min:0',
            'stock_minimo'    => 'nullable|integer|min:0',
            'stock_maximo'    => 'nullable|integer|min:0',
            'activo'          => 'nullable',
            'imagen'          => 'nullable|image|max:2048',
            'componentes'     => 'required|array|min:1',
            'componentes.*.producto_id' => 'required|integer|exists:productos,id',
            'componentes.*.cantidad'    => 'required|integer|min:1',
        ]);

        $imagenPath = request()->hasFile('imagen')
            ? request()->file('imagen')->store('sixpacks', 'public') : null;

        $id = \Illuminate\Support\Facades\DB::table('sixpacks')->insertGetId([
            'sucursal_id'    => $sucursal,
            'codigo_barras'  => $datos['codigo_barras'] ?? null,
            'nombre'         => $datos['nombre'],
            'imagen'         => $imagenPath,
            'categoria_id'   => $datos['categoria_id'] ?? null,
            'precio_compra'  => $datos['precio_compra'],
            'precio_venta'   => $datos['precio_venta'],
            'precio_mayoreo' => $datos['precio_mayoreo'] ?? 0,
            'stock_minimo'   => $datos['stock_minimo'] ?? 1,
            'stock_maximo'   => $datos['stock_maximo'] ?? 100,
            'activo'         => request('activo') ? 1 : 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        foreach (request('componentes') as $comp) {
            \Illuminate\Support\Facades\DB::table('sixpack_componentes')->insert([
                'sixpack_id'  => $id,
                'producto_id' => $comp['producto_id'],
                'cantidad'    => $comp['cantidad'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        return response()->json(\Illuminate\Support\Facades\DB::table('sixpacks')->find($id), 201);
    });

    Route::post('sixpacks/{id}', function ($sucursal, $id) {
        $datos = request()->validate([
            'nombre'          => 'required|string|max:255',
            'codigo_barras'   => 'nullable|string|max:100',
            'categoria_id'    => 'nullable|integer',
            'precio_compra'   => 'required|numeric|min:0',
            'precio_venta'    => 'required|numeric|min:0.01',
            'precio_mayoreo'  => 'nullable|numeric|min:0',
            'stock_minimo'    => 'nullable|integer|min:0',
            'stock_maximo'    => 'nullable|integer|min:0',
            'activo'          => 'nullable',
            'imagen'          => 'nullable|image|max:2048',
            'componentes'     => 'required|array|min:1',
            'componentes.*.producto_id' => 'required|integer|exists:productos,id',
            'componentes.*.cantidad'    => 'required|integer|min:1',
        ]);

        $update = [
            'codigo_barras'  => $datos['codigo_barras'] ?? null,
            'nombre'         => $datos['nombre'],
            'categoria_id'   => $datos['categoria_id'] ?? null,
            'precio_compra'  => $datos['precio_compra'],
            'precio_venta'   => $datos['precio_venta'],
            'precio_mayoreo' => $datos['precio_mayoreo'] ?? 0,
            'stock_minimo'   => $datos['stock_minimo'] ?? 1,
            'stock_maximo'   => $datos['stock_maximo'] ?? 100,
            'activo'         => request('activo') ? 1 : 0,
            'updated_at'     => now(),
        ];

        if (request()->hasFile('imagen')) {
            $update['imagen'] = request()->file('imagen')->store('sixpacks', 'public');
        }

        \Illuminate\Support\Facades\DB::table('sixpacks')
            ->where('sucursal_id', $sucursal)->where('id', $id)->update($update);

        \Illuminate\Support\Facades\DB::table('sixpack_componentes')->where('sixpack_id', $id)->delete();
        foreach (request('componentes') as $comp) {
            \Illuminate\Support\Facades\DB::table('sixpack_componentes')->insert([
                'sixpack_id'  => $id,
                'producto_id' => $comp['producto_id'],
                'cantidad'    => $comp['cantidad'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        return response()->json(\Illuminate\Support\Facades\DB::table('sixpacks')->find($id));
    });

    Route::delete('sixpacks/{id}', function ($sucursal, $id) {
        \Illuminate\Support\Facades\DB::table('sixpacks')
            ->where('sucursal_id', $sucursal)->where('id', $id)
            ->update(['deleted_at' => now()]);
        return response()->json(['ok' => true]);
    });
});

Route::get('/me', function () {
    $user = auth()->user();
    return response()->json([
        'id'          => $user->id,
        'name'        => $user->name,
        'nickname'    => $user->nickname,
        'email'       => $user->email,
        'rol'         => $user->rol,
        'sucursal_id' => $user->sucursal_id,
        'permisos'    => $user->rol === 'super_admin'
                            ? ['*']
                            : $user->getAllPermissions()->pluck('name')->values(),
    ]);
});
