<?php

use App\Http\Controllers\Api\Admin\CompraController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ProductoController;
use App\Http\Controllers\Api\Admin\ProveedorController;
use App\Http\Controllers\Api\Admin\SucursalController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::apiResource('sucursales', SucursalController::class)
    ->parameters(['sucursales' => 'sucursal']);
Route::patch('sucursales/{sucursal}/toggle', [SucursalController::class, 'toggle']);

Route::apiResource('users', UserController::class);

Route::prefix('sucursales/{sucursal}')->group(function () {
    Route::get('productos',          [ProductoController::class, 'index']);
    Route::post('productos',         [ProductoController::class, 'store']);
    Route::post('productos/{id}',    [ProductoController::class, 'update']);
    Route::delete('productos/{id}',  [ProductoController::class, 'destroy']);

    Route::get('compras',               [CompraController::class, 'index']);
    Route::post('compras',              [CompraController::class, 'store']);
    Route::patch('compras/{id}/anular', [CompraController::class, 'anular']);

    Route::get('proveedores',                    [ProveedorController::class, 'index']);
    Route::post('proveedores',                   [ProveedorController::class, 'store']);
    Route::put('proveedores/{proveedor}',        [ProveedorController::class, 'update']);
    Route::delete('proveedores/{proveedor}',     [ProveedorController::class, 'destroy']);
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
