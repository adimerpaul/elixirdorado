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
            'items.*.producto_id'     => 'nullable|integer',
            'items.*.sixpack_id'      => 'nullable|integer',
            'items.*.cantidad'        => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $sucursal) {
            // Cargar componentes de sixpacks
            $spComponentes = [];
            foreach ($data['items'] as $item) {
                if (!empty($item['sixpack_id'])) {
                    $spId = $item['sixpack_id'];
                    $spComponentes[$spId] ??= DB::table('sixpack_componentes')
                        ->where('sixpack_id', $spId)->get();
                }
            }

            // Agregar necesidades de stock (productos directos + componentes de sixpacks)
            $allNeeds = [];
            foreach ($data['items'] as $item) {
                if (!empty($item['producto_id'])) {
                    $allNeeds[$item['producto_id']] = ($allNeeds[$item['producto_id']] ?? 0) + $item['cantidad'];
                } elseif (!empty($item['sixpack_id'])) {
                    foreach ($spComponentes[$item['sixpack_id']] as $comp) {
                        $allNeeds[$comp->producto_id] = ($allNeeds[$comp->producto_id] ?? 0)
                            + ($comp->cantidad * $item['cantidad']);
                    }
                }
            }

            // Bloquear y validar stock
            $stocks = DB::table('productos')
                ->where('sucursal_id', $sucursal->id)
                ->whereIn('id', array_keys($allNeeds))
                ->lockForUpdate()
                ->pluck('stock_actual', 'id');

            foreach ($allNeeds as $prodId => $needed) {
                if (($stocks[$prodId] ?? 0) < $needed) {
                    $nombre = DB::table('productos')->find($prodId)?->nombre ?? "ID $prodId";
                    abort(422, "Stock insuficiente para \"$nombre\" (disponible: {$stocks[$prodId]}).");
                }
            }

            $subtotal = collect($data['items'])->sum(fn ($i) => $i['cantidad'] * $i['precio_unitario']);

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
                if (!empty($item['producto_id'])) {
                    DetalleVenta::create([
                        'venta_id'        => $venta->id,
                        'producto_id'     => $item['producto_id'],
                        'cantidad'        => $item['cantidad'],
                        'precio_unitario' => $item['precio_unitario'],
                        'subtotal'        => $item['cantidad'] * $item['precio_unitario'],
                    ]);
                    Producto::where('id', $item['producto_id'])
                        ->where('sucursal_id', $sucursal->id)
                        ->decrement('stock_actual', $item['cantidad']);
                    $this->aplicarFifo($item['producto_id'], $sucursal->id, $item['cantidad']);
                } elseif (!empty($item['sixpack_id'])) {
                    DB::table('detalle_ventas')->insert([
                        'venta_id'        => $venta->id,
                        'producto_id'     => null,
                        'sixpack_id'      => $item['sixpack_id'],
                        'cantidad'        => $item['cantidad'],
                        'precio_unitario' => $item['precio_unitario'],
                        'subtotal'        => $item['cantidad'] * $item['precio_unitario'],
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    foreach ($spComponentes[$item['sixpack_id']] as $comp) {
                        $needed = $comp->cantidad * $item['cantidad'];
                        Producto::where('id', $comp->producto_id)
                            ->where('sucursal_id', $sucursal->id)
                            ->decrement('stock_actual', $needed);
                        $this->aplicarFifo($comp->producto_id, $sucursal->id, $needed);
                    }
                }
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
            // Leer detalles directamente con DB para asegurar sixpack_id
            $detalles = DB::table('detalle_ventas')->where('venta_id', $venta->id)->get();

            foreach ($detalles as $detalle) {
                if (!empty($detalle->producto_id)) {
                    Producto::where('id', $detalle->producto_id)
                        ->where('sucursal_id', $sucursal->id)
                        ->increment('stock_actual', $detalle->cantidad);
                    $this->revertirFifo($detalle->producto_id, $sucursal->id, $detalle->cantidad);
                } elseif (!empty($detalle->sixpack_id)) {
                    $componentes = DB::table('sixpack_componentes')
                        ->where('sixpack_id', $detalle->sixpack_id)
                        ->get();
                    foreach ($componentes as $comp) {
                        $needed = $comp->cantidad * $detalle->cantidad;
                        Producto::where('id', $comp->producto_id)
                            ->where('sucursal_id', $sucursal->id)
                            ->increment('stock_actual', $needed);
                        $this->revertirFifo($comp->producto_id, $sucursal->id, $needed);
                    }
                }
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
