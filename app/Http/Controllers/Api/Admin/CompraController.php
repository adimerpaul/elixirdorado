<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(Request $request, Sucursal $sucursal)
    {
        $query = Compra::with([
                'user:id,name,nickname',
                'proveedor:id,nombre',
                'detalles.producto:id,nombre,codigo_barras',
            ])
            ->where('sucursal_id', $sucursal->id)
            ->withTrashed();

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        $compras = $query->latest()->get();

        $stats = [
            'total_activas'  => $compras->where('estado', 'activa')->sum('total'),
            'total_anuladas' => $compras->where('estado', 'anulada')->sum('total'),
            'count'          => $compras->where('estado', 'activa')->count(),
        ];

        return response()->json(['compras' => $compras, 'stats' => $stats]);
    }

    public function store(Request $request, Sucursal $sucursal)
    {
        $data = $request->validate([
            'proveedor_id'   => 'required|integer|exists:proveedores,id',
            'comentarios'    => 'nullable|string',
            'metodo_pago'    => 'required|in:efectivo,tarjeta,transferencia',
            'items'          => 'required|array|min:1',
            'items.*.producto_id'    => 'required|integer|exists:productos,id',
            'items.*.cantidad'       => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'items.*.precio_total'    => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $sucursal, $request) {
            $total = collect($data['items'])->sum(
                fn($i) => $i['precio_total'] ?? ($i['cantidad'] * $i['precio_unitario'])
            );

            $compra = Compra::create([
                'sucursal_id'  => $sucursal->id,
                'user_id'      => auth()->id(),
                'proveedor_id' => $data['proveedor_id'],
                'comentarios'  => $data['comentarios'] ?? null,
                'metodo_pago'  => $data['metodo_pago'],
                'total'        => $total,
                'estado'       => 'activa',
            ]);

            foreach ($data['items'] as $item) {
                $precioTotal = $item['precio_total'] ?? ($item['cantidad'] * $item['precio_unitario']);

                DetalleCompra::create([
                    'compra_id'       => $compra->id,
                    'producto_id'     => $item['producto_id'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'precio_total'    => $precioTotal,
                ]);

                Producto::where('id', $item['producto_id'])
                    ->where('sucursal_id', $sucursal->id)
                    ->increment('stock_actual', $item['cantidad'], [
                        'precio_compra' => $item['precio_unitario'],
                    ]);
            }

            $this->_last = $compra->load('user:id,name,nickname', 'proveedor:id,nombre', 'detalles.producto:id,nombre,codigo_barras');
        });

        return response()->json($this->_last, 201);
    }

    public function anular(Sucursal $sucursal, int $id)
    {
        $compra = Compra::withTrashed()
            ->where('sucursal_id', $sucursal->id)
            ->where('estado', 'activa')
            ->findOrFail($id);

        DB::transaction(function () use ($compra, $sucursal) {
            foreach ($compra->detalles as $detalle) {
                Producto::where('id', $detalle->producto_id)
                    ->where('sucursal_id', $sucursal->id)
                    ->decrement('stock_actual', $detalle->cantidad);
            }

            $compra->update(['estado' => 'anulada']);
            $compra->delete();
        });

        return response()->json($compra->fresh()->load('user:id,name,nickname', 'proveedor:id,nombre', 'detalles.producto:id,nombre'));
    }
}
