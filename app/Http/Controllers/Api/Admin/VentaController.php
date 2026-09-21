<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Venta;
use Illuminate\Http\Request;
use Carbon\Carbon;
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

        // El rango se interpreta en la zona horaria del negocio y se convierte a
        // UTC, que es como la BD guarda created_at.
        $tz = config('negocio.zona_horaria', 'America/La_Paz');

        if ($request->filled('desde')) {
            $hora = $request->input('hora_desde') ?: '00:00';
            $query->where('created_at', '>=', Carbon::parse("{$request->desde} {$hora}", $tz)
                ->startOfMinute()->setTimezone('UTC'));
        }
        if ($request->filled('hasta')) {
            $hora = $request->input('hora_hasta') ?: '23:59';
            $query->where('created_at', '<=', Carbon::parse("{$request->hasta} {$hora}", $tz)
                ->endOfMinute()->setTimezone('UTC'));
        }

        // Vendedores con ventas en el rango (antes de filtrar por vendedor, para el selector)
        $vendedores = \App\Models\User::whereIn('id', (clone $query)->select('usuario_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'nickname']);

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->integer('usuario_id'));
        }

        $ventas = $query->latest()->get();

        $ventasCompletadas = $ventas->where('estado', 'completada');

        // Ganancia = ingresos por ventas − costo (precio_unitario de compra × cantidad vendida)
        $costoTotal = $ventasCompletadas->flatMap->detalles->sum(function ($d) {
            return ($d->producto?->precio_compra ?? 0) * $d->cantidad;
        });

        $ingresos = $ventasCompletadas->sum('total');

        // Desglose por método de pago (solo ventas completadas)
        $porMetodo = [];
        // Métodos vigentes siempre; métodos antiguos (tarjeta, etc.) solo si hay ventas con ellos
        $metodos = collect(config('negocio.metodos_pago'))
            ->merge($ventasCompletadas->pluck('metodo_pago'))
            ->unique();
        foreach ($metodos as $metodo) {
            $grupo = $ventasCompletadas->where('metodo_pago', $metodo);
            $porMetodo[$metodo] = [
                'count' => $grupo->count(),
                'total' => round($grupo->sum('total'), 2),
            ];
        }
        // Desglose del mixto para saber cuánto entró en efectivo y cuánto por QR
        $mixtas = $ventasCompletadas->where('metodo_pago', 'mixto');
        $porMetodo['mixto']['efectivo'] = round($mixtas->sum('monto_efectivo'), 2);
        $porMetodo['mixto']['qr']       = round($mixtas->sum('monto_qr'), 2);

        // Total real recibido por canal (incluye la parte de las ventas mixtas)
        $ingresoEfectivo = round($porMetodo['efectivo']['total'] + $porMetodo['mixto']['efectivo'], 2);
        $ingresoQr       = round($porMetodo['qr']['total'] + $porMetodo['mixto']['qr'], 2);

        $stats = [
            'total_completadas' => $ingresos,
            'total_canceladas'  => $ventas->where('estado', 'cancelada')->sum('total'),
            'count'             => $ventasCompletadas->count(),
            'ganancia'          => round($ingresos - $costoTotal, 2),
            'por_metodo'        => $porMetodo,
            'ingreso_efectivo'  => $ingresoEfectivo,
            'ingreso_qr'        => $ingresoQr,
            'ver_totales'       => true,
        ];

        // Sin permiso de totales (ej. vendedores) no se envían montos agregados
        if (! $this->puedeVerTotales($sucursal)) {
            $stats = [
                'count'       => $stats['count'],
                'por_metodo'  => array_map(fn ($m) => ['count' => $m['count'] ?? 0], $porMetodo),
                'ver_totales' => false,
            ];
        }

        return response()->json(['ventas' => $ventas, 'stats' => $stats, 'vendedores' => $vendedores]);
    }

    public function store(Request $request, Sucursal $sucursal)
    {
        $data = $request->validate([
            'cliente_id'              => 'nullable|integer|exists:clientes,id',
            'comentarios'             => 'nullable|string|max:500',
            'metodo_pago'             => 'required|in:' . implode(',', config('negocio.metodos_pago')),
            'monto_efectivo'          => 'required_if:metodo_pago,mixto|nullable|numeric|min:0',
            'monto_qr'                => 'required_if:metodo_pago,mixto|nullable|numeric|min:0',
            'items'                 => 'required|array|min:1',
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

            // Pago mixto: efectivo + QR deben cubrir exactamente el total
            $esMixto = $data['metodo_pago'] === 'mixto';
            if ($esMixto && abs(($data['monto_efectivo'] + $data['monto_qr']) - $subtotal) > 0.009) {
                abort(422, 'En pago mixto, efectivo + QR debe ser igual al total (Bs ' . number_format($subtotal, 2) . ').');
            }

            $venta = Venta::create([
                'sucursal_id'  => $sucursal->id,
                'folio'        => 'TEMP-' . uniqid('', true),
                'usuario_id'   => auth()->id(),
                'cliente_id'   => $data['cliente_id'] ?? null,
                'subtotal'     => $subtotal,
                'iva'          => 0,
                'total'        => $subtotal,
                'metodo_pago'  => $data['metodo_pago'],
                'monto_efectivo' => $esMixto ? $data['monto_efectivo'] : null,
                'monto_qr'     => $esMixto ? $data['monto_qr'] : null,
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

    private function puedeVerTotales(Sucursal $sucursal): bool
    {
        $user = auth()->user();

        return $user->rol === 'super_admin'
            || $user->can("sucursal.{$sucursal->id}")
            || $user->can("sucursal.{$sucursal->id}.ventas.totales");
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
