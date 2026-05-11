<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SucursalController extends Controller
{
    public function index()
    {
        return response()->json(Sucursal::orderBy('nombre')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:100',
            'slug'           => 'required|string|max:50|unique:sucursales|regex:/^[a-z0-9_-]+$/',
            'direccion'      => 'nullable|string|max:255',
            'telefono'       => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:255',
            'admin_nombre'   => 'required|string|max:100',
            'admin_email'    => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ], [
            'slug.regex'         => 'El identificador solo puede tener minúsculas, números, "_" y "-".',
            'slug.unique'        => 'Ya existe una sucursal con ese identificador.',
            'admin_email.unique' => 'Ese correo ya está registrado.',
            'admin_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        DB::beginTransaction();
        try {
            $sucursal = Sucursal::create([
                'nombre'    => $data['nombre'],
                'slug'      => $data['slug'],
                'direccion' => $data['direccion'] ?? null,
                'telefono'  => $data['telefono'] ?? null,
                'email'     => $data['email'] ?? null,
                'activa'    => true,
            ]);

            User::create([
                'name'        => $data['admin_nombre'],
                'email'       => $data['admin_email'],
                'password'    => Hash::make($data['admin_password']),
                'rol'         => 'admin',
                'sucursal_id' => $sucursal->id,
            ]);

            DB::commit();
            return response()->json($sucursal, 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear la sucursal: ' . $e->getMessage()], 422);
        }
    }

    public function show(Sucursal $sucursal)
    {
        return response()->json($sucursal);
    }

    public function update(Request $request, Sucursal $sucursal)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:100',
            'direccion' => 'nullable|string|max:255',
            'telefono'  => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:255',
        ]);

        $sucursal->update($data);
        return response()->json($sucursal);
    }

    public function destroy(Sucursal $sucursal)
    {
        $sucursal->delete();
        return response()->json(null, 204);
    }

    public function toggle(Sucursal $sucursal)
    {
        $sucursal->update(['activa' => !$sucursal->activa]);
        return response()->json($sucursal);
    }
}
