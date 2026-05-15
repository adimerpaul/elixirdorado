<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index(Request $request, Sucursal $sucursal)
    {
        $query = Venta::with([
                'usuario:id,name,nickname',
                'cliente:id,nombre',
                'detalles.producto:id,nombre,codigo_barras,precio_compra',
            ])
            ->where('sucursal_id', $sucursal->id);

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        $ventas = $query->latest()->get();

        $ventasCompletadas = $ventas->where('estado', 'completada');

        // Ganancia = ingresos por ventas − costo (precio_unitario de compra × cantidad vendida)
        $costoTotal = $ventasCompletadas->flatMap->detalles->sum(function ($d) {
            return ($d->producto?->precio_compra ?? 0) * $d->cantidad;
        });

        $ingresos = $ventasCompletadas->sum('total');

        $stats = [
            'total_completadas' => $ingresos,
            'total_canceladas'  => $ventas->where('estado', 'cancelada')->sum('total'),
            'count'             => $ventasCompletadas->count(),
            'ganancia'          => round($ingresos - $costoTotal, 2),
        ];

        return response()->json(['ventas' => $ventas, 'stats' => $stats]);
    }

    public function store(Request $request, Sucursal $sucursal)
    {
        $data = $request->validate([
            'cliente_id'              => 'nullable|integer|exists:clientes,id',
            'comentarios'             => 'nullable|string|max:500',
            'metodo_pago'             => 'required|in:efectivo,tarjeta,transferencia',
            'items'                   => 'required|array|min:1',
            'items.*.producto_id'     => 'required|integer|exists:productos,id',
            'items.*.cantidad'        => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $sucursal) {
            $subtotal = collect($data['items'])->sum(
                fn ($i) => $i['cantidad'] * $i['precio_unitario']
            );

            $venta = Venta::create([
                'sucursal_id'  => $sucursal->id,
                'folio'        => 'TEMP-' . uniqid('', true),
                'usuario_id'   => auth()->id(),
                'cliente_id'   => $data['cliente_id'] ?? null,
                'subtotal'     => $subtotal,
                'iva'          => 0,
                'total'        => $subtotal,
                'metodo_pago'  => $data['metodo_pago'],
                'comentarios'  => $data['comentarios'] ?? null,
                'estado'       => 'completada',
            ]);

            $venta->update(['folio' => 'VTA-' . str_pad($venta->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($data['items'] as $item) {
                $producto = Producto::where('id', $item['producto_id'])
                    ->where('sucursal_id', $sucursal->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($producto->stock_actual < $item['cantidad']) {
                    abort(422, "Stock insuficiente para \"{$producto->nombre}\" (disponible: {$producto->stock_actual}).");
                }

                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $item['producto_id'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal'        => $item['cantidad'] * $item['precio_unitario'],
                ]);

                $producto->decrement('stock_actual', $item['cantidad']);

                $this->aplicarFifo($item['producto_id'], $sucursal->id, $item['cantidad']);
            }

            $this->_last = $venta->load(
                'usuario:id,name,nickname',
                'cliente:id,nombre',
                'detalles.producto:id,nombre,codigo_barras'
            );
        });

        return response()->json($this->_last, 201);
    }

    public function cancelar(Sucursal $sucursal, int $id)
    {
        $venta = Venta::with('detalles')
            ->where('sucursal_id', $sucursal->id)
            ->where('estado', 'completada')
            ->findOrFail($id);

        DB::transaction(function () use ($venta, $sucursal) {
            foreach ($venta->detalles as $detalle) {
                Producto::where('id', $detalle->producto_id)
                    ->where('sucursal_id', $sucursal->id)
                    ->increment('stock_actual', $detalle->cantidad);

                $this->revertirFifo($detalle->producto_id, $sucursal->id, $detalle->cantidad);
            }

            $venta->update(['estado' => 'cancelada']);
        });

        return response()->json(
            $venta->fresh()->load('usuario:id,name,nickname', 'cliente:id,nombre', 'detalles.producto:id,nombre')
        );
    }

    // ── FIFO helpers ──────────────────────────────────────────────

    private function aplicarFifo(int $productoId, int $sucursalId, int $cantidad): void
    {
        $lotes = DetalleCompra::whereHas('compra', fn ($q) =>
                $q->where('sucursal_id', $sucursalId)->where('estado', 'activa')
            )
            ->where('producto_id', $productoId)
            ->whereColumn('cantidad_vendida', '<', 'cantidad')
            ->orderBy('compra_id', 'asc')   // lote más antiguo primero
            ->lockForUpdate()
            ->get();

        $restante = $cantidad;
        foreach ($lotes as $lote) {
            if ($restante <= 0) break;
            $disponible = $lote->cantidad - $lote->cantidad_vendida;
            $tomar      = min($disponible, $restante);
            $lote->increment('cantidad_vendida', $tomar);
            $restante  -= $tomar;
        }
    }

    private function revertirFifo(int $productoId, int $sucursalId, int $cantidad): void
    {
        $lotes = DetalleCompra::whereHas('compra', fn ($q) =>
                $q->where('sucursal_id', $sucursalId)->where('estado', 'activa')
            )
            ->where('producto_id', $productoId)
            ->where('cantidad_vendida', '>', 0)
            ->orderBy('compra_id', 'desc')  // lote más reciente primero para revertir
            ->lockForUpdate()
            ->get();

        $restante = $cantidad;
        foreach ($lotes as $lote) {
            if ($restante <= 0) break;
            $tomar    = min($lote->cantidad_vendida, $restante);
            $lote->decrement('cantidad_vendida', $tomar);
            $restante -= $tomar;
        }
    }
}
