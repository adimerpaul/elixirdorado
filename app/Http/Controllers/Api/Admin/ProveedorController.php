<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(Request $request, Sucursal $sucursal)
    {
        $query = Proveedor::where('sucursal_id', $sucursal->id)->orderBy('nombre');

        if (!$request->boolean('todos')) {
            $query->where('activo', true);
        }

        return response()->json($query->get());
    }

    public function store(Request $request, Sucursal $sucursal)
    {
        $data = $request->validate([
            'nombre'   => 'required|string|max:150',
            'telefono' => 'nullable|string|max:30',
            'email'    => 'nullable|email|max:100',
        ]);

        $proveedor = Proveedor::create([
            'sucursal_id' => $sucursal->id,
            'nombre'      => $data['nombre'],
            'telefono'    => $data['telefono'] ?? null,
            'email'       => $data['email'] ?? null,
            'activo'      => true,
        ]);

        return response()->json($proveedor, 201);
    }

    public function update(Request $request, Sucursal $sucursal, Proveedor $proveedor)
    {
        abort_if($proveedor->sucursal_id !== $sucursal->id, 403);

        $data = $request->validate([
            'nombre'   => 'required|string|max:150',
            'telefono' => 'nullable|string|max:30',
            'email'    => 'nullable|email|max:100',
            'activo'   => 'boolean',
        ]);

        $proveedor->update($data);

        return response()->json($proveedor->fresh());
    }

    public function destroy(Sucursal $sucursal, Proveedor $proveedor)
    {
        abort_if($proveedor->sucursal_id !== $sucursal->id, 403);

        if ($proveedor->compras()->exists()) {
            return response()->json(['message' => 'No se puede eliminar: tiene compras asociadas.'], 422);
        }

        $proveedor->delete();

        return response()->json(['ok' => true]);
    }
}
