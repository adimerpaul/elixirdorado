<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    public function index(Sucursal $sucursal)
    {
        $productos  = Producto::with('categoria:id,nombre')
            ->where('sucursal_id', $sucursal->id)
            ->orderBy('nombre')
            ->get();

        $categorias = Categoria::where('sucursal_id', $sucursal->id)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json([
            'productos'  => $productos,
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request, Sucursal $sucursal)
    {
        $data = $request->validate([
            'codigo_barras'  => 'nullable|string|max:100',
            'nombre'         => 'required|string|max:255',
            'descripcion'    => 'nullable|string',
            'categoria_id'   => 'nullable|integer',
            'precio_compra'  => 'required|numeric|min:0',
            'precio_venta'   => 'required|numeric|min:0',
            'precio_mayoreo' => 'nullable|numeric|min:0',
            'stock_minimo'   => 'nullable|integer|min:0',
            'stock_maximo'   => 'nullable|integer|min:0',
            'activo'         => 'boolean',
            'imagen'         => 'nullable|file|image|max:2048',
        ]);

        $imagenPath = null;
        if ($request->hasFile('imagen')) {
            $imagenPath = $request->file('imagen')->store("productos/{$sucursal->slug}", 'public');
        }

        $producto = Producto::create([
            'sucursal_id'    => $sucursal->id,
            'codigo_barras'  => $data['codigo_barras'] ?? null,
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'] ?? null,
            'imagen'         => $imagenPath,
            'categoria_id'   => $data['categoria_id'] ?? null,
            'precio_compra'  => $data['precio_compra'],
            'precio_venta'   => $data['precio_venta'],
            'precio_mayoreo' => $data['precio_mayoreo'] ?? 0,
            'stock_actual'   => 0,
            'stock_minimo'   => $data['stock_minimo'] ?? 0,
            'stock_maximo'   => $data['stock_maximo'] ?? 100,
            'activo'         => $data['activo'] ? true : false,
        ]);

        return response()->json($producto->load('categoria:id,nombre'), 201);
    }

    public function update(Request $request, Sucursal $sucursal, int $id)
    {
        $producto = Producto::where('sucursal_id', $sucursal->id)->findOrFail($id);

        $data = $request->validate([
            'codigo_barras'  => 'nullable|string|max:100',
            'nombre'         => 'required|string|max:255',
            'descripcion'    => 'nullable|string',
            'categoria_id'   => 'nullable|integer',
            'precio_compra'  => 'required|numeric|min:0',
            'precio_venta'   => 'required|numeric|min:0',
            'precio_mayoreo' => 'nullable|numeric|min:0',
            'stock_minimo'   => 'nullable|integer|min:0',
            'stock_maximo'   => 'nullable|integer|min:0',
            'activo'         => 'nullable',
            'imagen'         => 'nullable|file|image|max:2048',
        ]);

        $imagenPath = $producto->imagen;
        if ($request->hasFile('imagen')) {
            if ($imagenPath) {
                Storage::disk('public')->delete($imagenPath);
            }
            $imagenPath = $request->file('imagen')->store("productos/{$sucursal->slug}", 'public');
        }
        error_log('estado:' . $data['activo'] ?? 'null');

        $producto->update([
            'codigo_barras'  => $data['codigo_barras'] ?? null,
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'] ?? null,
            'imagen'         => $imagenPath,
            'categoria_id'   => $data['categoria_id'] ?? null,
            'precio_compra'  => $data['precio_compra'],
            'precio_venta'   => $data['precio_venta'],
            'precio_mayoreo' => $data['precio_mayoreo'] ?? 0,
            'stock_minimo'   => $data['stock_minimo'] ?? 0,
            'stock_maximo'   => $data['stock_maximo'] ?? 100,
            'activo'         => $data['activo'] == 'true' ? true : false,
        ]);

        return response()->json($producto->load('categoria:id,nombre'));
    }

    public function destroy(Sucursal $sucursal, int $id)
    {
        $producto = Producto::where('sucursal_id', $sucursal->id)->findOrFail($id);

        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }

        $producto->delete();

        return response()->json(null, 204);
    }
}
