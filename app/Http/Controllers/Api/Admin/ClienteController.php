<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Sucursal $sucursal)
    {
        return response()->json(
            Cliente::where('sucursal_id', $sucursal->id)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'telefono'])
        );
    }

    public function store(Request $request, Sucursal $sucursal)
    {
        $data = $request->validate([
            'nombre'   => 'required|string|max:150',
            'telefono' => 'nullable|string|max:30',
            'email'    => 'nullable|email|max:100',
        ]);

        $cliente = Cliente::create([
            'sucursal_id' => $sucursal->id,
            'nombre'      => $data['nombre'],
            'telefono'    => $data['telefono'] ?? null,
            'email'       => $data['email'] ?? null,
            'activo'      => true,
        ]);

        return response()->json($cliente, 201);
    }
}
