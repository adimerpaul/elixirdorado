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

        $ventasSemana = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i)->format('Y-m-d');
            $ventasSemana[$fecha] = 0;
        }

        $ventasHoy = 0;
        $ventasMes = 0;
        $stats     = [];

        foreach ($sucursales as $s) {
            $vHoy = DB::table('ventas')
                ->where('sucursal_id', $s->id)
                ->whereDate('fecha_venta', $hoy)
                ->where('estado', '!=', 'cancelada')
                ->sum('total');

            $vMes = DB::table('ventas')
                ->where('sucursal_id', $s->id)
                ->whereMonth('fecha_venta', $mes)
                ->whereYear('fecha_venta', $anio)
                ->where('estado', '!=', 'cancelada')
                ->sum('total');

            $ventasHoy += $vHoy;
            $ventasMes += $vMes;

            $por_dia = DB::table('ventas')
                ->selectRaw('DATE(fecha_venta) as fecha, SUM(total) as total')
                ->where('sucursal_id', $s->id)
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
                'total_productos' => DB::table('productos')->where('sucursal_id', $s->id)->count(),
                'bajo_stock'      => DB::table('productos')
                    ->where('sucursal_id', $s->id)
                    ->whereColumn('stock_actual', '<=', 'stock_minimo')
                    ->count(),
            ];
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
