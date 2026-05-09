@extends('layouts.sucursal')
@section('titulo', 'Reportes')
@section('needs-charts', true)

@section('extra-styles')
.period-btn { padding: 6px 14px; border-radius: 4px; font-size: 13px; cursor: pointer;
    background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; transition: all 0.15s; }
.period-btn:hover, .period-btn.active { background: #2563eb; color: white; border-color: #2563eb; }
.stat-card { background: white; border-radius: 8px; padding: 16px 20px;
    border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.chart-card { background: white; border-radius: 8px; padding: 16px;
    border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.chart-wrap { position: relative; overflow: hidden; width: 100%; }
@media print {
    .topbar,.module-bar,.section-bar,.status-bar,.no-print { display:none !important; }
    body { background:white !important; }
    @page { size: A4; margin: 15mm; }
}
@endsection

@section('content')
{{-- Barra de sección --}}
<div class="section-bar flex items-center justify-between flex-wrap gap-2">
    <span class="font-bold text-blue-900 text-lg">REPORTES</span>
    <div class="flex gap-2 flex-wrap">
        <button onclick="cargarPeriodo('semana')" id="btn-semana" class="period-btn active">Semana Actual</button>
        <button onclick="cargarPeriodo('mes')"    id="btn-mes"    class="period-btn">Mes Actual</button>
        <button onclick="cargarPeriodo('mes-ant')" id="btn-mes-ant" class="period-btn">Mes Anterior</button>
        <button onclick="cargarPeriodo('ano')"    id="btn-ano"    class="period-btn">Año Actual</button>
    </div>
    <button onclick="window.print()" class="btn-secondary text-sm no-print">
        <i class="fas fa-print mr-1"></i>Imprimir
    </button>
</div>

<div class="p-4" style="padding-bottom:60px;">

    {{-- Título dinámico --}}
    <h2 class="text-xl font-bold text-gray-800 mb-4" id="titulo-periodo">
        Resumen de Ventas de la Semana Actual
    </h2>

    {{-- Tarjetas de resumen --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        <div class="stat-card border-l-4 border-green-500">
            <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Ventas Hoy</div>
            <div class="text-2xl font-black text-green-700 mt-1">Bs. {{ number_format($totalHoy, 2) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $cantidadHoy }} transacciones</div>
        </div>
        <div class="stat-card border-l-4 border-blue-500">
            <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Ventas del Mes</div>
            <div class="text-2xl font-black text-blue-700 mt-1">Bs. {{ number_format($totalMes, 2) }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $cantidadMes }} transacciones</div>
        </div>
        <div class="stat-card border-l-4 border-purple-500">
            <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Promedio/Venta</div>
            <div class="text-2xl font-black text-purple-700 mt-1">
                Bs. {{ $cantidadMes > 0 ? number_format($totalMes / $cantidadMes, 2) : '0.00' }}
            </div>
            <div class="text-xs text-gray-400 mt-1">este mes</div>
        </div>
        <div class="stat-card border-l-4 border-orange-500">
            <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Efectivo (semana)</div>
            <div class="text-2xl font-black text-orange-700 mt-1">
                Bs. {{ number_format($ventasPago->firstWhere('metodo_pago','efectivo')->total ?? 0, 2) }}
            </div>
            <div class="text-xs text-gray-400 mt-1">últimos 7 días</div>
        </div>
    </div>

    {{-- Gráficas fila 1 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">

        {{-- Gráfica: Ventas por día --}}
        <div class="chart-card">
            <h4 class="font-bold text-gray-700 mb-3">
                <i class="fas fa-chart-bar mr-2 text-blue-500"></i>Ventas por Día (últimos 7 días)
            </h4>
            <div class="chart-wrap" style="height:200px;">
                <canvas id="chart-dias"></canvas>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2 text-xs text-center">
                @foreach($ventasPago as $p)
                <div class="bg-gray-50 rounded p-2">
                    <div class="font-bold capitalize text-gray-700">{{ $p->metodo_pago }}</div>
                    <div class="text-green-600 font-bold">Bs. {{ number_format($p->total, 2) }}</div>
                    <div class="text-gray-400">{{ $p->cantidad }} ventas</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Gráfica: Ganancia por departamento --}}
        <div class="chart-card">
            <h4 class="font-bold text-gray-700 mb-3">
                <i class="fas fa-chart-pie mr-2 text-purple-500"></i>Ganancia por Departamento
            </h4>
            <div class="flex items-start gap-4">
                <div class="chart-wrap" style="flex:0 0 180px; height:180px;">
                    <canvas id="chart-dept"></canvas>
                </div>
                <div class="flex-1 text-sm">
                    @forelse($gananciaCat as $gc)
                    <div class="flex items-center justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-700">{{ $gc->categoria }}</span>
                        <span class="font-bold text-green-700">Bs. {{ number_format($gc->ganancia, 2) }}</span>
                    </div>
                    @empty
                    <p class="text-gray-400 text-center py-4">Sin ventas en este período</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Fila: Impuestos + Métodos de pago --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">

        {{-- Impuestos --}}
        <div class="chart-card">
            <h4 class="font-bold text-gray-700 mb-3">
                <i class="fas fa-receipt mr-2 text-red-500"></i>Impuestos (semana)
            </h4>
            @php
                $iva = (float) config('negocio.iva', 0.13);
                $totalIva = $ventasSemana->sum('total') * $iva / (1 + $iva);
                $totalSinIva = $ventasSemana->sum('total') - $totalIva;
            @endphp
            @if($ventasSemana->sum('total') > 0)
            <div class="space-y-2 text-sm">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Subtotal (sin IVA):</span>
                    <span class="font-bold">Bs. {{ number_format($totalSinIva, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">IVA (13%):</span>
                    <span class="font-bold text-red-600">Bs. {{ number_format($totalIva, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 text-base font-black">
                    <span>Total con IVA:</span>
                    <span class="text-blue-900">Bs. {{ number_format($ventasSemana->sum('total'), 2) }}</span>
                </div>
            </div>
            @else
            <div class="text-center py-6 text-gray-400">
                <i class="fas fa-receipt text-3xl mb-2 block opacity-30"></i>
                No se registró ninguna venta esta semana
            </div>
            @endif
        </div>

        {{-- Ventas por forma de pago detalle --}}
        <div class="chart-card">
            <h4 class="font-bold text-gray-700 mb-3">
                <i class="fas fa-wallet mr-2 text-green-500"></i>Desglose por Forma de Pago
            </h4>
            <div class="chart-wrap" style="height:180px;">
                <canvas id="chart-pago"></canvas>
            </div>
        </div>
    </div>

    {{-- Productos más vendidos --}}
    <div class="card overflow-hidden mb-5">
        <div class="p-3 border-b border-gray-200 flex items-center justify-between">
            <h4 class="font-bold text-gray-700">
                <i class="fas fa-star mr-2 text-yellow-500"></i>Productos Más Vendidos (últimos 30 días)
            </h4>
        </div>
        <table class="w-full text-sm">
            <thead class="table-header">
                <tr>
                    <th class="text-left p-3">#</th>
                    <th class="text-left p-3">Producto</th>
                    <th class="text-right p-3">Unidades Vendidas</th>
                    <th class="text-right p-3">Total Bs.</th>
                    <th class="p-3" style="width:150px;">Popularidad</th>
                </tr>
            </thead>
            <tbody>
                @php $maxUds = $productosTop->max('total_uds') ?: 1; @endphp
                @forelse($productosTop as $i => $p)
                <tr class="table-row border-b">
                    <td class="p-3 text-gray-400 font-bold">{{ $i + 1 }}</td>
                    <td class="p-3 font-medium text-gray-800">{{ $p->nombre }}</td>
                    <td class="p-3 text-right font-bold text-blue-700">{{ number_format($p->total_uds) }}</td>
                    <td class="p-3 text-right font-bold text-green-700">Bs. {{ number_format($p->total_bs, 2) }}</td>
                    <td class="p-3">
                        <div class="bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width:{{ round($p->total_uds / $maxUds * 100) }}%"></div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-10 text-gray-400">
                        <i class="fas fa-box text-3xl mb-2 block opacity-30"></i>
                        Sin ventas en los últimos 30 días
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Sub-tabs --}}
    <div class="flex gap-2 mb-4 no-print">
        <button class="nav-btn" onclick="window.location.href='/{{ $sucursal->slug }}/ventas'">
            <i class="fas fa-receipt text-blue-500"></i>Reporte de Ventas
        </button>
        <button class="nav-btn" onclick="window.location.href='/{{ $sucursal->slug }}/clientes'">
            <i class="fas fa-users text-green-500"></i>Ventas por Cliente
        </button>
        <button class="nav-btn" onclick="window.location.href='/{{ $sucursal->slug }}/inventario'">
            <i class="fas fa-warehouse text-teal-500"></i>Reporte de Inventario
        </button>
    </div>

</div>
@endsection

@section('scripts')
<script>
// ── Datos del servidor ────────────────────────────────────────────────
const diasData    = @json($ventasSemana);
const pagoData    = @json($ventasPago);
const deptData    = @json($gananciaCat);

// ── Colores ───────────────────────────────────────────────────────────
const COLORS = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#84cc16'];

// Destruir instancias previas de Chart.js si existen (evita gráficas "fantasma")
['chart-dias','chart-dept','chart-pago'].forEach(id => {
    const existing = Chart.getChart(id);
    if (existing) existing.destroy();
});

// ── Gráfica: Ventas por día ───────────────────────────────────────────
(function() {
    const ctx = document.getElementById('chart-dias').getContext('2d');
    // Generar últimos 7 días
    const labels = [], totales = [];
    for (let i = 6; i >= 0; i--) {
        const d = new Date(); d.setDate(d.getDate() - i);
        const key = d.toISOString().split('T')[0];
        const diasNames = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        labels.push(diasNames[d.getDay()]);
        const found = diasData.find(r => r.dia === key);
        totales.push(found ? parseFloat(found.total) : 0);
    }
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Ventas (Bs.)',
                data: totales,
                backgroundColor: totales.map(v => v > 0 ? '#3b82f6' : '#e2e8f0'),
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => 'Bs.' + v.toLocaleString() },
                    grid: { color: '#f1f5f9' }
                },
                x: { grid: { display: false } }
            }
        }
    });
})();

// ── Gráfica: Departamento (donut) ─────────────────────────────────────
(function() {
    const ctx = document.getElementById('chart-dept').getContext('2d');
    if (deptData.length === 0) {
        ctx.font = '13px sans-serif'; ctx.fillStyle = '#9ca3af';
        ctx.textAlign = 'center';
        ctx.fillText('Sin datos', 100, 100); return;
    }
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: deptData.map(d => d.categoria),
            datasets: [{ data: deptData.map(d => parseFloat(d.ganancia)), backgroundColor: COLORS, borderWidth: 2 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' Bs. ' + ctx.parsed.toFixed(2) } }
            },
            cutout: '60%'
        }
    });
})();

// ── Gráfica: Forma de pago (horizontal bar) ───────────────────────────
(function() {
    const ctx = document.getElementById('chart-pago').getContext('2d');
    const colors = { efectivo: '#22c55e', tarjeta: '#3b82f6', transferencia: '#a855f7' };
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: pagoData.map(p => p.metodo_pago.charAt(0).toUpperCase() + p.metodo_pago.slice(1)),
            datasets: [{
                data: pagoData.map(p => parseFloat(p.total)),
                backgroundColor: pagoData.map(p => colors[p.metodo_pago] || '#94a3b8'),
                borderRadius: 4,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { callback: v => 'Bs.' + v }, grid: { color: '#f1f5f9' } },
                y: { grid: { display: false } }
            }
        }
    });
})();

// ── Cambiar período ───────────────────────────────────────────────────
function cargarPeriodo(p) {
    const titulos = {
        semana: 'Resumen de Ventas de la Semana Actual',
        mes:    'Resumen de Ventas del Mes Actual',
        'mes-ant': 'Resumen de Ventas del Mes Anterior',
        ano:    'Resumen de Ventas del Año Actual'
    };
    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-' + p)?.classList.add('active');
    document.getElementById('titulo-periodo').textContent = titulos[p] || '';
    // En una versión futura, se haría una llamada AJAX para actualizar los datos
}
</script>
@endsection
