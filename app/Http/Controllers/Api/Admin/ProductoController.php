<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\ProductosExport;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
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

    public function historial(Sucursal $sucursal, int $id)
    {
        $producto = Producto::where('sucursal_id', $sucursal->id)->findOrFail($id);

        $compras = DetalleCompra::with([
                'compra:id,sucursal_id,created_at,estado,metodo_pago',
                'compra.proveedor:id,nombre',
            ])
            ->where('producto_id', $producto->id)
            ->whereHas('compra', fn ($q) => $q->where('sucursal_id', $sucursal->id))
            ->latest()
            ->get()
            ->map(fn ($d) => [
                'id'               => $d->id,
                'fecha'            => $d->compra->created_at,
                'compra_id'        => $d->compra_id,
                'proveedor'        => $d->compra->proveedor?->nombre ?? '—',
                'estado'           => $d->compra->estado,
                'metodo_pago'      => $d->compra->metodo_pago,
                'cantidad'         => $d->cantidad,
                'cantidad_vendida' => $d->cantidad_vendida,
                'disponible'       => $d->cantidad - $d->cantidad_vendida,
                'precio_unitario'  => $d->precio_unitario,
                'precio_total'     => $d->precio_total,
            ]);

        $ventas = DetalleVenta::with([
                'venta:id,sucursal_id,folio,created_at,estado,metodo_pago',
                'venta.cliente:id,nombre',
                'venta.usuario:id,name,nickname',
            ])
            ->where('producto_id', $producto->id)
            ->whereHas('venta', fn ($q) => $q->where('sucursal_id', $sucursal->id))
            ->latest()
            ->get()
            ->map(fn ($d) => [
                'id'              => $d->id,
                'fecha'           => $d->venta->created_at,
                'folio'           => $d->venta->folio,
                'venta_id'        => $d->venta_id,
                'cliente'         => $d->venta->cliente?->nombre ?? 'S/N',
                'usuario'         => $d->venta->usuario?->nickname ?? $d->venta->usuario?->name ?? '—',
                'estado'          => $d->venta->estado,
                'metodo_pago'     => $d->venta->metodo_pago,
                'cantidad'        => $d->cantidad,
                'precio_unitario' => $d->precio_unitario,
                'subtotal'        => $d->subtotal,
            ]);

        return response()->json([
            'producto' => ['id' => $producto->id, 'nombre' => $producto->nombre],
            'compras'  => $compras,
            'ventas'   => $ventas,
        ]);
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

    public function stockAlertas(Sucursal $sucursal)
    {
        $base = Producto::with('categoria:id,nombre')
            ->where('sucursal_id', $sucursal->id)
            ->where('activo', true);

        $bajoMinimo = (clone $base)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderBy('nombre')
            ->get()
            ->map(fn($p) => array_merge($p->toArray(), [
                'diferencia' => $p->stock_minimo - $p->stock_actual,
                'excedido'   => $p->stock_actual < $p->stock_minimo,
                'porcentaje' => $p->stock_minimo > 0
                    ? round(($p->stock_actual / $p->stock_minimo) * 100)
                    : 0,
            ]));

        $sobreMaximo = (clone $base)
            ->where('stock_maximo', '>', 0)
            ->whereColumn('stock_actual', '>=', 'stock_maximo')
            ->orderBy('nombre')
            ->get()
            ->map(fn($p) => array_merge($p->toArray(), [
                'diferencia' => $p->stock_actual - $p->stock_maximo,
                'excedido'   => $p->stock_actual > $p->stock_maximo,
                'porcentaje' => round(($p->stock_actual / $p->stock_maximo) * 100),
            ]));

        return response()->json([
            'bajo_minimo'  => $bajoMinimo,
            'sobre_maximo' => $sobreMaximo,
        ]);
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
