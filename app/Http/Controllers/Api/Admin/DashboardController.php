<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $sucursales = Sucursal::all();
        $hoy        = Carbon::today()->format('Y-m-d');
        $mes        = now()->month;
        $anio       = now()->year;

        $stats       = [];
        $ventasHoy   = 0;
        $ventasMes   = 0;
        $ventasSemana = [];

        // Build 7-day labels
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i)->format('Y-m-d');
            $ventasSemana[$fecha] = 0;
        }

        foreach ($sucursales as $s) {
            try {
                config(['database.connections.tenant.database' => $s->base_datos]);
                DB::purge('tenant');
                DB::reconnect('tenant');

                $vHoy = DB::connection('tenant')->table('ventas')
                    ->whereDate('fecha_venta', $hoy)
                    ->where('estado', '!=', 'cancelada')
                    ->sum('total');

                $vMes = DB::connection('tenant')->table('ventas')
                    ->whereMonth('fecha_venta', $mes)
                    ->whereYear('fecha_venta', $anio)
                    ->where('estado', '!=', 'cancelada')
                    ->sum('total');

                $ventasHoy += $vHoy;
                $ventasMes += $vMes;

                // Aggregate last 7 days per sucursal
                $por_dia = DB::connection('tenant')->table('ventas')
                    ->selectRaw('DATE(fecha_venta) as fecha, SUM(total) as total')
                    ->where('estado', '!=', 'cancelada')
                    ->whereBetween('fecha_venta', [Carbon::today()->subDays(6), Carbon::now()])
                    ->groupByRaw('DATE(fecha_venta)')
                    ->get();

                foreach ($por_dia as $row) {
                    if (isset($ventasSemana[$row->fecha])) {
                        $ventasSemana[$row->fecha] += $row->total;
                    }
                }

                $stats[] = [
                    'id'              => $s->id,
                    'nombre'          => $s->nombre,
                    'slug'            => $s->slug,
                    'activa'          => $s->activa,
                    'ventas_hoy'      => (float) $vHoy,
                    'ventas_mes'      => (float) $vMes,
                    'total_productos' => DB::connection('tenant')->table('productos')->count(),
                    'bajo_stock'      => DB::connection('tenant')->table('productos')
                        ->whereColumn('stock_actual', '<=', 'stock_minimo')->count(),
                ];
            } catch (\Throwable $e) {
                $stats[] = [
                    'id'              => $s->id,
                    'nombre'          => $s->nombre,
                    'slug'            => $s->slug,
                    'activa'          => $s->activa,
                    'ventas_hoy'      => 0,
                    'ventas_mes'      => 0,
                    'total_productos' => 0,
                    'bajo_stock'      => 0,
                    'error'           => true,
                ];
            }
        }

        return response()->json([
            'sucursales_total'   => $sucursales->count(),
            'sucursales_activas' => $sucursales->where('activa', true)->count(),
            'usuarios_total'     => User::count(),
            'ventas_hoy'         => (float) $ventasHoy,
            'ventas_mes'         => (float) $ventasMes,
            'ventas_semana'      => [
                'labels' => array_keys($ventasSemana),
                'data'   => array_values($ventasSemana),
            ],
            'sucursales'         => $stats,
        ]);
    }
}
