<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\SucursalController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::apiResource('sucursales', SucursalController::class);
Route::patch('sucursales/{sucursal}/toggle', [SucursalController::class, 'toggle']);

Route::apiResource('users', UserController::class);

Route::get('/me', fn () => response()->json(auth()->user()));
