<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Estadísticas de ventas: montos, histograma, métodos de pago, productos más
 * vendidos, mejores vendedores y ganancia. Las fechas se interpretan en la
 * zona horaria del negocio; la BD guarda created_at en UTC.
 *
 * Ganancia = ventas − costo, con costo = precio_compra actual del producto ×
 * cantidad (los sixpacks suman el costo de sus componentes).
 */
class EstadisticaController extends Controller
{
    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    private const DIAS  = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    // GET sucursales/{sucursal}/estadisticas
    public function sucursal(Request $request, Sucursal $sucursal)
    {
        $user = auth()->user();
        abort_unless(
            $user->rol === 'super_admin'
                || $user->can("sucursal.{$sucursal->id}")
                || $user->can("sucursal.{$sucursal->id}.dashboard"),
            403, 'No tienes permiso para ver el dashboard de esta sucursal.'
        );

        return response()->json($this->reporte($request, collect([$sucursal])));
    }

    // GET estadisticas/sucursales — todas las sucursales, con desglose por sucursal
    public function sucursales(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->rol === 'super_admin' || $user->can('dashboard'), 403,
            'No tienes permiso para ver el dashboard general.');

        return response()->json($this->reporte($request, Sucursal::orderBy('nombre')->get()));
    }

    private function reporte(Request $request, $sucursales): array
    {
        $request->validate([
            'periodo' => 'nullable|in:hoy,ayer,semana,mes,anio,rango',
            'desde'   => 'required_if:periodo,rango|nullable|date',
            'hasta'   => 'required_if:periodo,rango|nullable|date',
        ]);

        $r   = $this->rango($request);
        $ids = $sucursales->pluck('id')->all();

        $actual   = $this->agregados($ids, $r['ini'], $r['fin'], $r['bucketSql']);
        $anterior = $this->agregados($ids, $r['prevIni'], $r['prevFin'], "'total'");

        // ── Serie (histograma) ────────────────────────────────────
        $serie = [];
        foreach ($r['buckets'] as $key => $label) {
            $serie[$key] = ['key' => (string) $key, 'label' => $label, 'ventas' => 0.0, 'costo' => 0.0, 'count' => 0];
        }
        foreach ($actual['ventas'] as $v) {
            $k = $this->bucketKey($v->bucket, $r['gran']);
            if (! isset($serie[$k])) continue;
            $serie[$k]['ventas'] += (float) $v->total;
            $serie[$k]['count']  += (int) $v->count;
        }
        foreach ($actual['detalle'] as $d) {
            $k = $this->bucketKey($d->bucket, $r['gran']);
            if (isset($serie[$k])) $serie[$k]['costo'] += $d->costo;
        }
        $serie = array_values(array_map(fn ($s) => [
            'key'      => $s['key'],
            'label'    => $s['label'],
            'ventas'   => round($s['ventas'], 2),
            'ganancia' => round($s['ventas'] - $s['costo'], 2),
            'count'    => $s['count'],
        ], $serie));

        // ── Productos más vendidos ────────────────────────────────
        $productos = [];
        foreach ($actual['detalle'] as $d) {
            $k = $d->producto_id ? "p{$d->producto_id}" : "s{$d->sixpack_id}";
            $productos[$k] ??= [
                'nombre'   => $d->nombre,
                'sixpack'  => ! $d->producto_id,
                'cantidad' => 0, 'monto' => 0.0, 'costo' => 0.0,
            ];
            $productos[$k]['cantidad'] += (int) $d->cantidad;
            $productos[$k]['monto']    += (float) $d->monto;
            $productos[$k]['costo']    += $d->costo;
        }
        $productos = collect($productos)->map(fn ($p) => [
            'nombre'   => $p['nombre'],
            'sixpack'  => $p['sixpack'],
            'cantidad' => $p['cantidad'],
            'monto'    => round($p['monto'], 2),
            'ganancia' => round($p['monto'] - $p['costo'], 2),
        ]);

        // ── Por sucursal ──────────────────────────────────────────
        $porSucursal = $sucursales->map(function ($s) use ($actual, $anterior) {
            $ventas   = $actual['ventas']->where('sucursal_id', $s->id);
            $total    = (float) $ventas->sum('total');
            $count    = (int) $ventas->sum('count');
            $costo    = $actual['detalle']->where('sucursal_id', $s->id)->sum('costo');
            $prevTot  = (float) $anterior['ventas']->where('sucursal_id', $s->id)->sum('total');
            $prevCost = $anterior['detalle']->where('sucursal_id', $s->id)->sum('costo');

            return [
                'id'             => $s->id,
                'nombre'         => $s->nombre,
                'activa'         => (bool) $s->activa,
                'ventas'         => round($total, 2),
                'count'          => $count,
                'costo'          => round($costo, 2),
                'ganancia'       => round($total - $costo, 2),
                'margen'         => $total > 0 ? round(($total - $costo) / $total * 100, 1) : null,
                'ticket'         => $count > 0 ? round($total / $count, 2) : 0,
                'ventas_prev'    => round($prevTot, 2),
                'ganancia_prev'  => round($prevTot - $prevCost, 2),
            ];
        })->values();

        $total     = (float) $actual['ventas']->sum('total');
        $count     = (int) $actual['ventas']->sum('count');
        $costo     = $actual['detalle']->sum('costo');
        $prevTotal = (float) $anterior['ventas']->sum('total');
        $prevCount = (int) $anterior['ventas']->sum('count');
        $prevCosto = $anterior['detalle']->sum('costo');

        return [
            'periodo' => [
                'tipo'         => $r['tipo'],
                'desde'        => $r['ini']->toDateString(),
                'hasta'        => $r['fin']->toDateString(),
                'granularidad' => $r['gran'],
                'prev_desde'   => $r['prevIni']->toDateString(),
                'prev_hasta'   => $r['prevFin']->toDateString(),
            ],
            'resumen' => [
                'ventas'         => round($total, 2),
                'count'          => $count,
                'costo'          => round($costo, 2),
                'ganancia'       => round($total - $costo, 2),
                'margen'         => $total > 0 ? round(($total - $costo) / $total * 100, 1) : null,
                'ticket'         => $count > 0 ? round($total / $count, 2) : 0,
                'unidades'       => (int) $actual['detalle']->sum('cantidad'),
                'canceladas'       => $actual['canceladas']['count'],
                'canceladas_monto' => $actual['canceladas']['total'],
                'prev' => [
                    'ventas'   => round($prevTotal, 2),
                    'count'    => $prevCount,
                    'ganancia' => round($prevTotal - $prevCosto, 2),
                    'ticket'   => $prevCount > 0 ? round($prevTotal / $prevCount, 2) : 0,
                ],
            ],
            'serie'          => $serie,
            'metodos'        => $this->metodos($ids, $r['ini'], $r['fin']),
            'top_cantidad'   => $productos->sortByDesc('cantidad')->take(10)->values(),
            'top_monto'      => $productos->sortByDesc('monto')->take(10)->values(),
            'top_ganancia'   => $productos->sortByDesc('ganancia')->take(10)->values(),
            'vendedores'     => $this->vendedores($ids, $r['ini'], $r['fin']),
            'por_sucursal'   => $porSucursal,
        ];
    }

    /**
     * Resuelve el periodo pedido a [ini, fin] (hora local), el periodo anterior
     * con el que se compara, y la granularidad del histograma.
     */
    private function rango(Request $request): array
    {
        $tz      = config('negocio.zona_horaria', 'America/La_Paz');
        $now     = Carbon::now($tz);
        $tipo    = $request->input('periodo', 'hoy');

        switch ($tipo) {
            case 'ayer':
                $ini = $now->copy()->subDay()->startOfDay();
                $fin = $ini->copy()->endOfDay();
                $prevIni = $ini->copy()->subDay();
                $prevFin = $prevIni->copy()->endOfDay();
                break;
            case 'semana':
                $ini = $now->copy()->startOfWeek(Carbon::MONDAY);
                $fin = $ini->copy()->endOfWeek(Carbon::SUNDAY);
                $prevIni = $ini->copy()->subWeek();
                $prevFin = $fin->copy()->subWeek();
                break;
            case 'mes':
                $ini = $now->copy()->startOfMonth();
                $fin = $now->copy()->endOfMonth();
                $prevIni = $ini->copy()->subMonthNoOverflow()->startOfMonth();
                $prevFin = $prevIni->copy()->endOfMonth();
                break;
            case 'anio':
                $ini = $now->copy()->startOfYear();
                $fin = $now->copy()->endOfYear();
                $prevIni = $ini->copy()->subYear();
                $prevFin = $prevIni->copy()->endOfYear();
                break;
            case 'rango':
                $ini = Carbon::parse($request->desde, $tz)->startOfDay();
                $fin = Carbon::parse($request->hasta, $tz)->endOfDay();
                if ($fin->lt($ini)) {
                    [$ini, $fin] = [$fin->copy()->startOfDay(), $ini->copy()->endOfDay()];
                }
                $dias    = (int) round($ini->diffInDays($fin->copy()->addSecond()));
                $prevFin = $ini->copy()->subSecond();
                $prevIni = $ini->copy()->subDays($dias);
                break;
            default:
                $tipo = 'hoy';
                $ini = $now->copy()->startOfDay();
                $fin = $now->copy()->endOfDay();
                $prevIni = $ini->copy()->subDay();
                $prevFin = $prevIni->copy()->endOfDay();
        }

        // Periodo en curso: comparar contra el mismo tramo transcurrido del anterior
        // (ej. mes hasta el día 21 vs. mes pasado hasta el día 21).
        if ($now->between($ini, $fin)) {
            $transcurrido = (int) $ini->diffInSeconds($now);
            $corte = $prevIni->copy()->addSeconds($transcurrido);
            if ($corte->lt($prevFin)) $prevFin = $corte;
        }

        $dias = (int) round($ini->diffInDays($fin->copy()->addSecond()));
        $gran = match (true) {
            in_array($tipo, ['hoy', 'ayer']) || $dias <= 1 => 'hora',
            $tipo === 'anio' || $dias > 92                 => 'mes',
            default                                        => 'dia',
        };

        // Expresión SQL del bucket en hora local (offset fijo, sin tablas de zonas)
        $offset = $ini->format('P');
        $local  = "CONVERT_TZ(v.created_at, '+00:00', '{$offset}')";
        $bucketSql = match ($gran) {
            'hora' => "HOUR({$local})",
            'dia'  => "DATE({$local})",
            'mes'  => "DATE_FORMAT({$local}, '%Y-%m')",
        };

        // Buckets del histograma hasta "ahora" (no se dibujan días futuros)
        $hastaBuckets = $fin->lt($now) ? $fin : $now;
        $buckets = [];
        if ($gran === 'hora') {
            $ultima = $hastaBuckets->isSameDay($ini) ? $hastaBuckets->hour : 23;
            for ($h = 0; $h <= $ultima; $h++) {
                $buckets[$h] = sprintf('%02d:00', $h);
            }
        } elseif ($gran === 'dia') {
            for ($d = $ini->copy(); $d->lte($hastaBuckets); $d->addDay()) {
                $buckets[$d->toDateString()] = self::DIAS[$d->dayOfWeek] . ' ' . $d->format('d/m');
            }
        } else {
            for ($d = $ini->copy()->startOfMonth(); $d->lte($hastaBuckets); $d->addMonthNoOverflow()) {
                $buckets[$d->format('Y-m')] = self::MESES[$d->month - 1] . ($tipo === 'anio' ? '' : ' ' . $d->format('y'));
            }
        }

        return compact('tipo', 'ini', 'fin', 'prevIni', 'prevFin', 'gran', 'bucketSql', 'buckets');
    }

    private function bucketKey($bucket, string $gran): string|int
    {
        return $gran === 'hora' ? (int) $bucket : (string) $bucket;
    }

    /** Ventas por sucursal/bucket y detalle con costo, para un rango. */
    private function agregados(array $ids, Carbon $ini, Carbon $fin, string $bucketSql): array
    {
        $desde = $ini->copy()->setTimezone('UTC');
        $hasta = $fin->copy()->setTimezone('UTC');

        $ventas = DB::table('ventas as v')
            ->selectRaw("v.sucursal_id, {$bucketSql} as bucket, SUM(v.total) as total, COUNT(*) as count")
            ->whereIn('v.sucursal_id', $ids)
            ->where('v.estado', 'completada')
            ->whereBetween('v.created_at', [$desde, $hasta])
            ->groupByRaw("v.sucursal_id, {$bucketSql}")
            ->get();

        $detalle = DB::table('detalle_ventas as d')
            ->join('ventas as v', 'v.id', '=', 'd.venta_id')
            ->selectRaw("v.sucursal_id, {$bucketSql} as bucket, d.producto_id, d.sixpack_id,
                SUM(d.cantidad) as cantidad, SUM(d.subtotal) as monto")
            ->whereIn('v.sucursal_id', $ids)
            ->where('v.estado', 'completada')
            ->whereBetween('v.created_at', [$desde, $hasta])
            ->groupByRaw("v.sucursal_id, {$bucketSql}, d.producto_id, d.sixpack_id")
            ->get();

        // Costos unitarios (productos eliminados incluidos: se lee la tabla directo)
        $prodIds = $detalle->pluck('producto_id')->filter()->unique();
        $spIds   = $detalle->pluck('sixpack_id')->filter()->unique();

        $productos = $prodIds->isEmpty() ? collect() : DB::table('productos')
            ->whereIn('id', $prodIds)->get(['id', 'nombre', 'precio_compra'])->keyBy('id');

        $sixpacks = $spIds->isEmpty() ? collect() : DB::table('sixpacks')
            ->whereIn('id', $spIds)->pluck('nombre', 'id');

        $costoSixpack = $spIds->isEmpty() ? collect() : DB::table('sixpack_componentes as c')
            ->join('productos as p', 'p.id', '=', 'c.producto_id')
            ->whereIn('c.sixpack_id', $spIds)
            ->groupBy('c.sixpack_id')
            ->selectRaw('c.sixpack_id, SUM(c.cantidad * p.precio_compra) as costo')
            ->pluck('costo', 'sixpack_id');

        foreach ($detalle as $d) {
            if ($d->producto_id) {
                $p = $productos[$d->producto_id] ?? null;
                $d->nombre = $p->nombre ?? "Producto #{$d->producto_id}";
                $unit      = (float) ($p->precio_compra ?? 0);
            } else {
                $d->nombre = $sixpacks[$d->sixpack_id] ?? "Sixpack #{$d->sixpack_id}";
                $unit      = (float) ($costoSixpack[$d->sixpack_id] ?? 0);
            }
            $d->costo = $unit * (int) $d->cantidad;
        }

        $canceladas = DB::table('ventas')
            ->whereIn('sucursal_id', $ids)
            ->where('estado', 'cancelada')
            ->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        return [
            'ventas'     => $ventas,
            'detalle'    => $detalle,
            'canceladas' => ['count' => (int) $canceladas->count, 'total' => round((float) $canceladas->total, 2)],
        ];
    }

    /**
     * Desglose por método de pago. El pago mixto se reparte en su parte de
     * efectivo y de QR para saber cuánto entró realmente por cada canal.
     */
    private function metodos(array $ids, Carbon $ini, Carbon $fin): array
    {
        $rows = DB::table('ventas')
            ->whereIn('sucursal_id', $ids)
            ->where('estado', 'completada')
            ->whereBetween('created_at', [$ini->copy()->setTimezone('UTC'), $fin->copy()->setTimezone('UTC')])
            ->groupBy('metodo_pago')
            ->selectRaw('metodo_pago, COUNT(*) as count, SUM(total) as total,
                COALESCE(SUM(monto_efectivo), 0) as efectivo, COALESCE(SUM(monto_qr), 0) as qr')
            ->get()
            ->keyBy('metodo_pago');

        // Métodos vigentes siempre; métodos antiguos (tarjeta, etc.) solo si hay ventas con ellos
        $metodos = [];
        foreach (collect(config('negocio.metodos_pago'))->merge($rows->keys())->unique() as $m) {
            $row = $rows[$m] ?? null;
            $metodos[] = [
                'metodo' => $m,
                'count'  => (int) ($row->count ?? 0),
                'total'  => round((float) ($row->total ?? 0), 2),
            ];
        }

        $mixto = $rows['mixto'] ?? null;

        return [
            'lista'         => $metodos,
            'mixto_efectivo' => round((float) ($mixto->efectivo ?? 0), 2),
            'mixto_qr'       => round((float) ($mixto->qr ?? 0), 2),
            // Efectivo / QR reales = método puro + su parte del mixto
            'efectivo_real' => round((float) ($rows['efectivo']->total ?? 0) + (float) ($mixto->efectivo ?? 0), 2),
            'qr_real'       => round((float) ($rows['qr']->total ?? 0) + (float) ($mixto->qr ?? 0), 2),
        ];
    }

    private function vendedores(array $ids, Carbon $ini, Carbon $fin)
    {
        return DB::table('ventas as v')
            ->leftJoin('users as u', 'u.id', '=', 'v.usuario_id')
            ->whereIn('v.sucursal_id', $ids)
            ->where('v.estado', 'completada')
            ->whereBetween('v.created_at', [$ini->copy()->setTimezone('UTC'), $fin->copy()->setTimezone('UTC')])
            ->groupBy('v.usuario_id', 'u.name', 'u.nickname')
            ->selectRaw('v.usuario_id, u.name, u.nickname, COUNT(*) as count, SUM(v.total) as total')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id'     => $r->usuario_id,
                'nombre' => $r->nickname ?: ($r->name ?? "Usuario #{$r->usuario_id}"),
                'count'  => (int) $r->count,
                'total'  => round((float) $r->total, 2),
                'ticket' => $r->count > 0 ? round($r->total / $r->count, 2) : 0,
            ]);
    }
}
