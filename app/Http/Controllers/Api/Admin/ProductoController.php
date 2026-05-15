<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\ProductosExport;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductoController extends Controller
{
    public function index(Sucursal $sucursal)
    {
        $productos  = $this->productosConCantidadActiva($sucursal)
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
            'activo'         => 'nullable',
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
            'activo'         => $request->boolean('activo', true),
        ]);

        return response()->json($this->productoConCantidadActiva($producto->id), 201);
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
            'activo'         => $request->has('activo') ? $request->boolean('activo') : $producto->activo,
        ]);

        return response()->json($this->productoConCantidadActiva($producto->id));
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

    public function exportExcel(Request $request, Sucursal $sucursal)
    {
        $scope = $request->query('scope') === 'existing' ? 'existing' : 'all';
        $productos = $this->productosConCantidadActiva($sucursal, $scope)->orderBy('nombre')->get();
        $filename = "productos-{$sucursal->slug}-{$scope}.xlsx";

        return Excel::download(new ProductosExport($productos), $filename);
    }

    public function exportPdf(Request $request, Sucursal $sucursal)
    {
        $scope = $request->query('scope') === 'existing' ? 'existing' : 'all';
        $productos = $this->productosConCantidadActiva($sucursal, $scope)->orderBy('nombre')->get();
        $filename = "productos-{$sucursal->slug}-{$scope}.pdf";

        return Pdf::loadView('exports.productos-pdf', [
            'productos' => $productos,
            'sucursal' => $sucursal,
            'scope' => $scope,
        ])->setPaper('letter', 'landscape')->download($filename);
    }

    private function productoConCantidadActiva(int $id): Producto
    {
        return $this->productosConCantidadActiva()->findOrFail($id);
    }

    private function productosConCantidadActiva(?Sucursal $sucursal = null, string $scope = 'all')
    {
        $query = Producto::with('categoria:id,nombre')
            ->withSum([
                'detalleCompras as cantidad_compras_activas' => fn($q) => $q->whereHas(
                    'compra',
                    fn($compra) => $compra->where('estado', 'activa')
                ),
            ], 'cantidad');

        if ($sucursal) {
            $query->where('sucursal_id', $sucursal->id);
        }

        if ($scope === 'existing') {
            $query->whereHas('detalleCompras', fn($q) => $q->whereHas(
                'compra',
                fn($compra) => $compra->where('estado', 'activa')
            ));
        }

        return $query;
    }
}
