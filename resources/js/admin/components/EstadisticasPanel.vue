<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Tooltip, Legend, Filler,
    BarElement, LineElement, PointElement, CategoryScale, LinearScale,
} from 'chart.js';
import axios from 'axios';

ChartJS.register(Tooltip, Legend, Filler, BarElement, LineElement, PointElement, CategoryScale, LinearScale);

// sucursalId = null → todas las sucursales (dashboard general)
const props = defineProps({
    sucursalId: { type: [Number, String], default: null },
});

// Colores de serie (paleta categórica validada: slot 1 azul, slot 2 naranja)
const C_VENTAS   = '#2a78d6';
const C_GANANCIA = '#eb6834';
const GRID       = '#f1f5f9';
const TICK       = '#64748b';

const PERIODOS = [
    { key: 'hoy',    label: 'Hoy' },
    { key: 'ayer',   label: 'Ayer' },
    { key: 'semana', label: 'Semana' },
    { key: 'mes',    label: 'Mes' },
    { key: 'anio',   label: 'Año' },
    { key: 'rango',  label: 'Entre fechas' },
];

const hoyISO = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const periodo = ref('hoy');
const desde   = ref(hoyISO().slice(0, 8) + '01');
const hasta   = ref(hoyISO());
const loading = ref(true);
const error   = ref('');
const data    = ref(null);
const topPor  = ref('cantidad');

const url = computed(() => props.sucursalId
    ? `/api/admin/sucursales/${props.sucursalId}/estadisticas`
    : '/api/admin/estadisticas/sucursales');

async function load() {
    if (periodo.value === 'rango' && (!desde.value || !hasta.value)) return;
    loading.value = true;
    error.value   = '';
    try {
        const params = { periodo: periodo.value };
        if (periodo.value === 'rango') Object.assign(params, { desde: desde.value, hasta: hasta.value });
        const res = await axios.get(url.value, { params });
        data.value = res.data;
    } catch (e) {
        error.value = e.response?.data?.message ?? 'No se pudieron cargar las estadísticas.';
        data.value  = null;
    } finally {
        loading.value = false;
    }
}

function setPeriodo(p) {
    periodo.value = p;
    if (p !== 'rango') load();
}

watch(() => props.sucursalId, load);
onMounted(load);

// ── Formato ───────────────────────────────────────────────────
const nf2 = new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const nf0 = new Intl.NumberFormat('es-BO', { maximumFractionDigits: 0 });
const fmtBs  = n => `Bs ${nf2.format(n ?? 0)}`;
const fmtInt = n => nf0.format(n ?? 0);
const fmtCorto = n => {
    const v = Math.abs(n ?? 0);
    if (v >= 1e6) return `${(n / 1e6).toFixed(1)}M`;
    if (v >= 1e3) return `${(n / 1e3).toFixed(1)}k`;
    return nf0.format(n ?? 0);
};

function delta(actual, previo) {
    if (!previo) return null;
    return ((actual - previo) / Math.abs(previo)) * 100;
}

const compLabel = computed(() => {
    const p = data.value?.periodo;
    if (!p) return '';
    return {
        hoy:    'vs. ayer a esta hora',
        ayer:   'vs. anteayer',
        semana: 'vs. semana pasada',
        mes:    'vs. mes pasado',
        anio:   'vs. año pasado',
    }[p.tipo] ?? `vs. ${fmtFecha(p.prev_desde)} – ${fmtFecha(p.prev_hasta)}`;
});

function fmtFecha(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
}

const rangoTexto = computed(() => {
    const p = data.value?.periodo;
    if (!p) return '';
    return p.desde === p.hasta ? fmtFecha(p.desde) : `${fmtFecha(p.desde)} – ${fmtFecha(p.hasta)}`;
});

// ── KPIs ──────────────────────────────────────────────────────
const kpis = computed(() => {
    const r = data.value?.resumen;
    if (!r) return [];
    return [
        { label: 'Ventas',          valor: fmtBs(r.ventas),   delta: delta(r.ventas, r.prev.ventas), sub: `${fmtInt(r.count)} venta(s)` },
        { label: 'Ganancia',        valor: fmtBs(r.ganancia), delta: delta(r.ganancia, r.prev.ganancia), sub: r.margen !== null ? `Margen ${r.margen}%` : 'Sin ventas' },
        { label: 'N° de ventas',    valor: fmtInt(r.count),   delta: delta(r.count, r.prev.count), sub: `${fmtInt(r.unidades)} unidades` },
        { label: 'Ticket promedio', valor: fmtBs(r.ticket),   delta: delta(r.ticket, r.prev.ticket), sub: 'por venta' },
    ];
});

// ── Histograma ────────────────────────────────────────────────
const tituloSerie = computed(() => ({
    hora: 'Ventas por hora',
    dia:  'Ventas por día',
    mes:  'Ventas por mes',
}[data.value?.periodo?.granularidad] ?? 'Ventas'));

// Tipo de gráfico (barras = histograma | línea); se recuerda por navegador
const TIPOS_GRAFICO = [
    { key: 'barras', label: 'Barras' },
    { key: 'linea',  label: 'Línea' },
];
const tipoGrafico = ref((() => {
    try { return localStorage.getItem('dash.tipoGrafico') || 'barras'; } catch { return 'barras'; }
})());
watch(tipoGrafico, v => { try { localStorage.setItem('dash.tipoGrafico', v); } catch { /* sin storage */ } });

const serieData = computed(() => {
    const s = data.value?.serie ?? [];
    const linea = tipoGrafico.value === 'linea';
    const ds = (label, key, color) => linea
        ? { label, data: s.map(x => x[key]), borderColor: color, backgroundColor: color + '1f', fill: true,
            borderWidth: 2, tension: 0.3, pointRadius: s.length > 31 ? 0 : 3, pointHoverRadius: 5,
            pointBackgroundColor: color, pointBorderColor: '#fff', pointBorderWidth: 2 }
        : { label, data: s.map(x => x[key]), backgroundColor: color, borderRadius: 4, borderSkipped: 'start',
            maxBarThickness: 28, borderColor: '#fff', borderWidth: { left: 1, right: 1 } };
    return {
        labels: s.map(x => x.label),
        datasets: [ds('Ventas', 'ventas', C_VENTAS), ds('Ganancia', 'ganancia', C_GANANCIA)],
    };
});

function barOptions(extraTooltip) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        datasets: { bar: { categoryPercentage: 0.8, barPercentage: 0.9 } },
        plugins: {
            legend: {
                position: 'top', align: 'end',
                labels: { boxWidth: 10, boxHeight: 10, color: TICK, font: { size: 11 } },
            },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.dataset.label}: ${fmtBs(ctx.parsed.y)}`,
                    ...(extraTooltip ? { afterBody: extraTooltip } : {}),
                },
            },
        },
        scales: {
            y: { beginAtZero: true, grid: { color: GRID }, border: { display: false }, ticks: { color: TICK, font: { size: 10 }, callback: v => fmtCorto(v) } },
            x: { grid: { display: false }, ticks: { color: TICK, font: { size: 10 }, maxRotation: 0, autoSkip: true } },
        },
    };
}

const serieOptions = computed(() => barOptions(items => {
    const x = data.value?.serie?.[items[0]?.dataIndex];
    return x ? `\n ${fmtInt(x.count)} venta(s)` : '';
}));

const serieVacia = computed(() => !(data.value?.serie ?? []).some(x => x.ventas || x.ganancia));

// ── Métodos de pago ───────────────────────────────────────────
const pagoLabel = {
    efectivo: 'Efectivo', tarjeta: 'Tarjeta', qr: 'QR',
    transferencia: 'Transferencia', credito: 'Crédito', mixto: 'Mixto (Ef. + QR)',
};
const metodos = computed(() => {
    const lista = data.value?.metodos?.lista ?? [];
    const max   = Math.max(...lista.map(m => m.total), 0);
    const total = lista.reduce((s, m) => s + m.total, 0);
    return lista.map(m => ({
        ...m,
        label: pagoLabel[m.metodo] ?? m.metodo,
        pct:   total > 0 ? (m.total / total) * 100 : 0,
        ancho: max > 0 ? (m.total / max) * 100 : 0,
    }));
});

// ── Top productos ─────────────────────────────────────────────
const TOP_TABS = [
    { key: 'cantidad', label: 'Cantidad' },
    { key: 'monto',    label: 'Monto' },
    { key: 'ganancia', label: 'Ganancia' },
];
const topProductos = computed(() => {
    const lista = data.value?.[`top_${topPor.value}`] ?? [];
    const max   = Math.max(...lista.map(p => p[topPor.value]), 0);
    return lista.map(p => ({ ...p, ancho: max > 0 ? Math.max(0, (p[topPor.value] / max) * 100) : 0 }));
});
const fmtTop = p => topPor.value === 'cantidad' ? `${fmtInt(p.cantidad)} u.` : fmtBs(p[topPor.value]);
const subTop = p => topPor.value === 'cantidad' ? fmtBs(p.monto) : `${fmtInt(p.cantidad)} u.`;

// ── Vendedores ────────────────────────────────────────────────
const vendedores = computed(() => {
    const lista = data.value?.vendedores ?? [];
    const max   = Math.max(...lista.map(v => v.total), 0);
    return lista.map(v => ({ ...v, ancho: max > 0 ? (v.total / max) * 100 : 0 }));
});

// ── Por sucursal (solo dashboard general) ─────────────────────
const multi = computed(() => !props.sucursalId);
const sucursalesData = computed(() => {
    const s = (data.value?.por_sucursal ?? []).filter(x => x.activa || x.ventas);
    return {
        labels: s.map(x => x.nombre),
        datasets: [
            { label: 'Ventas',   data: s.map(x => x.ventas),   backgroundColor: C_VENTAS,   borderRadius: 4, borderSkipped: 'start', maxBarThickness: 36 },
            { label: 'Ganancia', data: s.map(x => x.ganancia), backgroundColor: C_GANANCIA, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 36 },
        ],
    };
});
const sucursalesOptions = computed(() => barOptions(null));
const totalesSucursales = computed(() => {
    const s = data.value?.por_sucursal ?? [];
    const ventas   = s.reduce((a, x) => a + x.ventas, 0);
    const ganancia = s.reduce((a, x) => a + x.ganancia, 0);
    return {
        ventas, ganancia,
        count:  s.reduce((a, x) => a + x.count, 0),
        costo:  s.reduce((a, x) => a + x.costo, 0),
        margen: ventas > 0 ? ((ganancia / ventas) * 100).toFixed(1) : null,
    };
});
</script>


<template>
  <div class="flex flex-col gap-2">

    <!-- Filtros de periodo -->
    <div class="bg-white rounded-lg border border-gray-100 px-2.5 py-1.5 flex flex-wrap items-center gap-2">
      <div class="flex flex-wrap gap-0.5 bg-gray-100 p-0.5 rounded-md">
        <button v-for="p in PERIODOS" :key="p.key" @click="setPeriodo(p.key)"
          :class="['px-2.5 py-1 rounded text-xs font-semibold transition-colors',
            periodo === p.key ? 'bg-slate-800 text-white' : 'text-gray-600 hover:bg-white']">
          {{ p.label }}
        </button>
      </div>
      <template v-if="periodo === 'rango'">
        <input v-model="desde" type="date" aria-label="Desde"
          class="border border-gray-200 rounded-md px-2 py-0.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
        <span class="text-xs text-gray-400">a</span>
        <input v-model="hasta" type="date" aria-label="Hasta"
          class="border border-gray-200 rounded-md px-2 py-0.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button @click="load"
          class="bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1 rounded-md text-xs font-semibold">
          Aplicar
        </button>
      </template>
      <p v-if="data" class="ml-auto text-xs text-gray-400">
        {{ rangoTexto }}
        <span v-if="data.resumen.canceladas" class="ml-2 text-gray-400">
          · {{ data.resumen.canceladas }} cancelada(s) por {{ fmtBs(data.resumen.canceladas_monto) }} (no suman)
        </span>
      </p>
    </div>

    <div v-if="error" class="bg-red-50 text-red-700 text-xs rounded-lg px-3 py-2">{{ error }}</div>

    <div v-if="loading && !data" class="flex items-center justify-center h-64">
      <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
    </div>

    <template v-if="data">
      <div :class="['flex flex-col gap-2 transition-opacity', loading ? 'opacity-50 pointer-events-none' : '']">

        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
          <div v-for="k in kpis" :key="k.label" class="bg-white rounded-lg border border-gray-100 px-3 py-2">
            <div class="flex items-center justify-between gap-2">
              <p class="text-[11px] font-medium text-gray-500">{{ k.label }}</p>
              <span v-if="k.delta !== null" :title="compLabel"
                :class="['inline-flex items-center gap-0.5 text-[11px] font-semibold tabular-nums',
                  k.delta >= 0 ? 'text-green-700' : 'text-red-700']">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path v-if="k.delta >= 0" stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/>
                  <path v-else stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5l15 15m0 0V8.25m0 11.25H8.25"/>
                </svg>
                {{ Math.abs(k.delta).toFixed(1) }}%
              </span>
            </div>
            <p class="text-lg font-bold text-gray-800 leading-tight tabular-nums">{{ k.valor }}</p>
            <p class="text-[11px] text-gray-400">{{ k.sub }}</p>
          </div>
        </div>

        <!-- Fila: evolución + métodos de pago -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">

          <!-- Evolución (barras / línea) -->
          <div class="lg:col-span-2 bg-white rounded-lg border border-gray-100 p-2.5">
            <div class="flex items-start justify-between gap-2 mb-1">
              <div>
                <h3 class="text-sm font-semibold text-gray-700 leading-tight">{{ tituloSerie }}</h3>
                <p class="text-[11px] text-gray-400">Monto vendido y ganancia en Bs · {{ compLabel }}</p>
              </div>
              <div class="flex gap-0.5 bg-gray-100 p-0.5 rounded-md" role="group" aria-label="Tipo de gráfico">
                <button v-for="t in TIPOS_GRAFICO" :key="t.key" @click="tipoGrafico = t.key"
                  :aria-pressed="tipoGrafico === t.key"
                  :class="['flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold transition-colors',
                    tipoGrafico === t.key ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                  <svg v-if="t.key === 'barras'" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 20h18M6 16v-5M11 16V6M16 16v-8"/>
                  </svg>
                  <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 17l5-6 4 4 8-9"/>
                  </svg>
                  {{ t.label }}
                </button>
              </div>
            </div>
            <div class="h-56 relative">
              <Bar v-if="tipoGrafico === 'barras'" :data="serieData" :options="serieOptions" />
              <Line v-else :data="serieData" :options="serieOptions" />
              <p v-if="serieVacia" class="absolute inset-0 flex items-center justify-center text-xs text-gray-400">
                Sin ventas en este periodo
              </p>
            </div>
          </div>

          <!-- Métodos de pago -->
          <div class="bg-white rounded-lg border border-gray-100 p-2.5">
            <h3 class="text-sm font-semibold text-gray-700 leading-tight">Métodos de pago</h3>
            <p class="text-[11px] text-gray-400 mb-2">Cuánto entró por cada medio</p>

            <div class="grid grid-cols-2 gap-1.5 mb-2">
              <div class="rounded-md bg-gray-50 px-2 py-1.5">
                <p class="text-[10px] uppercase tracking-wide text-gray-500">Total efectivo</p>
                <p class="text-sm font-bold text-gray-800 tabular-nums">{{ fmtBs(data.metodos.efectivo_real) }}</p>
                <p v-if="data.metodos.mixto_efectivo" class="text-[10px] text-gray-400">incl. {{ fmtBs(data.metodos.mixto_efectivo) }} mixto</p>
              </div>
              <div class="rounded-md bg-gray-50 px-2 py-1.5">
                <p class="text-[10px] uppercase tracking-wide text-gray-500">Total QR</p>
                <p class="text-sm font-bold text-gray-800 tabular-nums">{{ fmtBs(data.metodos.qr_real) }}</p>
                <p v-if="data.metodos.mixto_qr" class="text-[10px] text-gray-400">incl. {{ fmtBs(data.metodos.mixto_qr) }} mixto</p>
              </div>
            </div>

            <ul class="space-y-1.5">
              <li v-for="m in metodos" :key="m.metodo" :title="`${m.label}: ${fmtBs(m.total)} en ${m.count} venta(s)`">
                <div class="flex items-baseline justify-between text-xs mb-0.5">
                  <span class="text-gray-600">{{ m.label }} <span class="text-gray-400">· {{ fmtInt(m.count) }}</span></span>
                  <span class="tabular-nums text-gray-800 font-semibold">
                    {{ fmtBs(m.total) }} <span class="text-gray-400 font-normal">{{ m.pct.toFixed(0) }}%</span>
                  </span>
                </div>
                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full" :style="{ width: `${m.ancho}%`, background: C_VENTAS }"></div>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- Comparativa por sucursal (general) -->
        <div v-if="multi" class="grid grid-cols-1 lg:grid-cols-3 gap-2">
          <div class="bg-white rounded-lg border border-gray-100 p-2.5">
            <h3 class="text-sm font-semibold text-gray-700 leading-tight">Ventas y ganancia por sucursal</h3>
            <p class="text-[11px] text-gray-400 mb-1">Mismo periodo para todas</p>
            <div class="h-48">
              <Bar :data="sucursalesData" :options="sucursalesOptions" />
            </div>
          </div>
          <div class="lg:col-span-2 bg-white rounded-lg border border-gray-100 p-2.5 overflow-x-auto">
            <table class="w-full text-xs">
              <thead>
                <tr class="text-left text-gray-400 uppercase text-[10px] border-b border-gray-100">
                  <th class="py-1 pr-2">Sucursal</th>
                  <th class="py-1 px-2 text-right">Ventas</th>
                  <th class="py-1 px-2 text-right">N°</th>
                  <th class="py-1 px-2 text-right">Costo</th>
                  <th class="py-1 px-2 text-right">Ganancia</th>
                  <th class="py-1 px-2 text-right">Margen</th>
                  <th class="py-1 pl-2 text-right">Ticket</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in data.por_sucursal" :key="s.id" class="border-b border-gray-50 hover:bg-gray-50">
                  <td class="py-1 pr-2 font-medium text-gray-700">
                    {{ s.nombre }}
                    <span v-if="!s.activa" class="ml-1 text-[10px] text-gray-400">(inactiva)</span>
                  </td>
                  <td class="py-1 px-2 text-right tabular-nums text-gray-700 whitespace-nowrap">
                    {{ fmtBs(s.ventas) }}
                    <span v-if="delta(s.ventas, s.ventas_prev) !== null"
                      :class="['text-[10px] ml-1', delta(s.ventas, s.ventas_prev) >= 0 ? 'text-green-700' : 'text-red-700']">
                      {{ delta(s.ventas, s.ventas_prev) >= 0 ? '▲' : '▼' }}{{ Math.abs(delta(s.ventas, s.ventas_prev)).toFixed(1) }}%
                    </span>
                  </td>
                  <td class="py-1 px-2 text-right tabular-nums text-gray-600">{{ fmtInt(s.count) }}</td>
                  <td class="py-1 px-2 text-right tabular-nums text-gray-500">{{ fmtBs(s.costo) }}</td>
                  <td class="py-1 px-2 text-right tabular-nums font-semibold text-gray-800">{{ fmtBs(s.ganancia) }}</td>
                  <td class="py-1 px-2 text-right tabular-nums text-gray-600">{{ s.margen !== null ? `${s.margen}%` : '—' }}</td>
                  <td class="py-1 pl-2 text-right tabular-nums text-gray-600">{{ fmtBs(s.ticket) }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="font-semibold text-gray-800 border-t-2 border-gray-200">
                  <td class="py-1 pr-2">Total</td>
                  <td class="py-1 px-2 text-right tabular-nums">{{ fmtBs(totalesSucursales.ventas) }}</td>
                  <td class="py-1 px-2 text-right tabular-nums">{{ fmtInt(totalesSucursales.count) }}</td>
                  <td class="py-1 px-2 text-right tabular-nums">{{ fmtBs(totalesSucursales.costo) }}</td>
                  <td class="py-1 px-2 text-right tabular-nums">{{ fmtBs(totalesSucursales.ganancia) }}</td>
                  <td class="py-1 px-2 text-right tabular-nums">{{ totalesSucursales.margen !== null ? `${totalesSucursales.margen}%` : '—' }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Fila: vendedores + productos más vendidos -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">

          <!-- Mejores vendedores -->
          <div class="bg-white rounded-lg border border-gray-100 p-2.5">
            <h3 class="text-sm font-semibold text-gray-700 leading-tight">Ventas por usuario</h3>
            <p class="text-[11px] text-gray-400 mb-2">Por monto vendido</p>
            <p v-if="!vendedores.length" class="text-xs text-gray-400 py-6 text-center">Sin ventas en este periodo</p>
            <ol class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
              <li v-for="(v, i) in vendedores" :key="v.id" :title="`${v.nombre}: ${fmtBs(v.total)} en ${v.count} venta(s)`">
                <div class="flex items-baseline justify-between text-xs mb-0.5 gap-2">
                  <span class="text-gray-700 truncate">
                    <span class="inline-block w-4 text-gray-400 tabular-nums">{{ i + 1 }}.</span>{{ v.nombre }}
                  </span>
                  <span class="tabular-nums text-gray-800 font-semibold whitespace-nowrap">{{ fmtBs(v.total) }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full" :style="{ width: `${v.ancho}%`, background: C_VENTAS }"></div>
                  </div>
                  <span class="text-[10px] text-gray-400 whitespace-nowrap tabular-nums">{{ fmtInt(v.count) }} vtas · {{ fmtBs(v.ticket) }}</span>
                </div>
              </li>
            </ol>
          </div>

          <!-- Productos más vendidos -->
          <div class="lg:col-span-2 bg-white rounded-lg border border-gray-100 p-2.5">
            <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
              <div>
                <h3 class="text-sm font-semibold text-gray-700 leading-tight">Productos más vendidos</h3>
                <p class="text-[11px] text-gray-400">Top 10 del periodo</p>
              </div>
              <div class="flex gap-0.5 bg-gray-100 p-0.5 rounded-md">
                <button v-for="t in TOP_TABS" :key="t.key" @click="topPor = t.key"
                  :class="['px-2 py-0.5 rounded text-[11px] font-semibold transition-colors',
                    topPor === t.key ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
                  {{ t.label }}
                </button>
              </div>
            </div>
            <p v-if="!topProductos.length" class="text-xs text-gray-400 py-6 text-center">Sin ventas en este periodo</p>
            <ol class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-1.5">
              <li v-for="(p, i) in topProductos" :key="p.nombre + i"
                :title="`${p.nombre}: ${fmtInt(p.cantidad)} u. · ${fmtBs(p.monto)} · ganancia ${fmtBs(p.ganancia)}`">
                <div class="flex items-baseline justify-between text-xs mb-0.5 gap-2">
                  <span class="text-gray-700 truncate">
                    <span class="inline-block w-5 text-gray-400 tabular-nums">{{ i + 1 }}.</span>{{ p.nombre }}
                    <span v-if="p.sixpack" class="ml-1 text-[10px] text-gray-400">(pack)</span>
                  </span>
                  <span class="whitespace-nowrap tabular-nums">
                    <span class="text-gray-800 font-semibold">{{ fmtTop(p) }}</span>
                    <span class="text-[10px] text-gray-400 ml-1">{{ subTop(p) }}</span>
                  </span>
                </div>
                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full"
                    :style="{ width: `${p.ancho}%`, background: topPor === 'ganancia' ? C_GANANCIA : C_VENTAS }"></div>
                </div>
              </li>
            </ol>
          </div>
        </div>

        <p class="text-[10px] text-gray-400">
          Ganancia = ventas − costo, con el precio de compra actual de cada producto (los packs suman el costo de sus componentes).
        </p>
      </div>
    </template>
  </div>
</template>
