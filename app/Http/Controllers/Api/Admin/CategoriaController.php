<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index(Sucursal $sucursal)
    {
        return response()->json(
            Categoria::where('sucursal_id', $sucursal->id)
                ->withCount('productos')
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
        );
    }

    public function store(Request $request, Sucursal $sucursal)
    {
        $data = $request->validate([
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('categorias')->where('sucursal_id', $sucursal->id),
            ],
        ], ['nombre.unique' => 'Ya existe una categoría con ese nombre.']);

        $categoria = Categoria::create([
            'sucursal_id' => $sucursal->id,
            'nombre'      => trim($data['nombre']),
        ]);

        return response()->json($categoria->loadCount('productos'), 201);
    }

    public function update(Request $request, Sucursal $sucursal, Categoria $categoria)
    {
        abort_if($categoria->sucursal_id !== $sucursal->id, 403);

        $data = $request->validate([
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('categorias')->where('sucursal_id', $sucursal->id)->ignore($categoria->id),
            ],
        ], ['nombre.unique' => 'Ya existe una categoría con ese nombre.']);

        $categoria->update(['nombre' => trim($data['nombre'])]);

        return response()->json($categoria->loadCount('productos'));
    }

    public function destroy(Sucursal $sucursal, Categoria $categoria)
    {
        abort_if($categoria->sucursal_id !== $sucursal->id, 403);

        // Los productos y sixpacks asociados quedan "Sin categoría" (FK nullOnDelete)
        $categoria->delete();

        return response()->json(['ok' => true]);
    }
}
