<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth.js';

const route    = useRoute();
const router   = useRouter();
const auth     = useAuthStore();
const sucId    = computed(() => route.params.sucursalId);
const sucursal = computed(() => auth.sucursales.find(s => s.id == sucId.value));

const activeTab = computed(() => route.name === 'sucursal.vencimientos.vencidos' ? 'vencidos' : 'por_vencer');
const lotes     = ref([]);
const loading   = ref(false);
const search    = ref('');
const rango     = ref(30); // días hacia adelante en "Por vencer"; null = todos

const RANGOS = [
    { value: 7,    label: '7 días' },
    { value: 15,   label: '15 días' },
    { value: 30,   label: '30 días' },
    { value: 60,   label: '60 días' },
    { value: 90,   label: '90 días' },
    { value: 180,  label: '6 meses' },
    { value: null, label: 'Todos' },
];

async function load() {
    if (!sucId.value) return;
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/productos/vencimientos`);
        lotes.value = data.lotes;
    } finally {
        loading.value = false;
    }
}

watch(sucId, load, { immediate: true });

function setTab(tab) {
    const name = tab === 'vencidos' ? 'sucursal.vencimientos.vencidos' : 'sucursal.vencimientos.por_vencer';
    router.push({ name, params: { sucursalId: sucId.value } });
}

const vencidos  = computed(() => lotes.value.filter(l => l.dias < 0).sort((a, b) => a.dias - b.dias));
const porVencer = computed(() => lotes.value.filter(l => l.dias >= 0 && (rango.value === null || l.dias <= rango.value)));

const lista = computed(() => {
    const base = activeTab.value === 'vencidos' ? vencidos.value : porVencer.value;
    const s = search.value.trim().toLowerCase();
    if (!s) return base;
    return base.filter(l =>
        (l.producto?.nombre ?? '').toLowerCase().includes(s) ||
        (l.producto?.codigo_barras ?? '').toLowerCase().includes(s) ||
        (l.lote ?? '').toLowerCase().includes(s)
    );
});

const totalUnidades = computed(() => lista.value.reduce((sum, l) => sum + l.disponible, 0));
const totalValor    = computed(() => lista.value.reduce((sum, l) => sum + parseFloat(l.valor), 0));
const productosDistintos = computed(() => new Set(lista.value.map(l => l.producto_id)).size);

function diasLabel(dias) {
    if (dias < 0)   return `Vencido hace ${-dias} día(s)`;
    if (dias === 0) return 'Vence hoy';
    return `Vence en ${dias} día(s)`;
}

function diasColor(dias) {
    if (dias <= 0)  return 'bg-red-100 text-red-700';
    if (dias <= 30) return 'bg-orange-100 text-orange-700';
    if (dias <= 90) return 'bg-yellow-100 text-yellow-700';
    return 'bg-green-100 text-green-700';
}

const fmtBs        = v => `Bs ${parseFloat(v ?? 0).toFixed(2)}`;
const fmtFechaCorta = d => d ? d.slice(0, 10).split('-').reverse().join('/') : '—';
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-4">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Vencimientos</h2>
        <p class="text-gray-400 text-xs mt-0.5">{{ sucursal?.nombre }} · lotes con stock disponible</p>
      </div>
      <button @click="load" :disabled="loading"
        class="self-start sm:self-auto flex items-center gap-1.5 border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors disabled:opacity-50">
        <svg :class="['w-3.5 h-3.5', loading ? 'animate-spin' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
        </svg>
        Actualizar
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-4 bg-gray-100 p-1 rounded-xl w-fit">
      <button @click="setTab('por_vencer')"
        :class="['px-4 py-2 rounded-lg text-xs font-semibold transition-colors flex items-center gap-2',
          activeTab === 'por_vencer' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
        </svg>
        Por vencer
        <span v-if="porVencer.length"
          class="bg-orange-500 text-white text-xs rounded-full px-1.5 py-0.5 leading-none min-w-[18px] text-center">
          {{ porVencer.length }}
        </span>
      </button>
      <button @click="setTab('vencidos')"
        :class="['px-4 py-2 rounded-lg text-xs font-semibold transition-colors flex items-center gap-2',
          activeTab === 'vencidos' ? 'bg-white text-red-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
        </svg>
        Vencidos
        <span v-if="vencidos.length"
          class="bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 leading-none min-w-[18px] text-center">
          {{ vencidos.length }}
        </span>
      </button>
    </div>

    <!-- Filtros -->
    <div class="flex flex-wrap gap-2 mb-3 items-center">
      <div class="relative">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
        <input v-model="search" type="text" placeholder="Producto, código o lote..."
          class="border border-gray-200 rounded-lg pl-8 pr-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white w-56">
      </div>
      <div v-if="activeTab === 'por_vencer'" class="flex items-center gap-2">
        <label class="text-xs text-gray-500">Vencen en los próximos</label>
        <select v-model="rango"
          class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
          <option v-for="r in RANGOS" :key="String(r.value)" :value="r.value">{{ r.label }}</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading && !lotes.length" class="flex justify-center py-12">
      <svg class="animate-spin w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
    </div>

    <template v-else>
      <!-- Resumen -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
        <div :class="['rounded-xl p-3 text-white', activeTab === 'vencidos' ? 'bg-red-600' : 'bg-orange-500']">
          <p class="text-xs text-white/75">Lotes</p>
          <p class="text-xl font-bold leading-tight">{{ lista.length }}</p>
          <p class="text-[11px] text-white/75">{{ productosDistintos }} producto(s)</p>
        </div>
        <div class="rounded-xl p-3 bg-white border border-gray-100 shadow-sm">
          <p class="text-xs text-gray-400">Unidades</p>
          <p class="text-xl font-bold leading-tight text-gray-800">{{ totalUnidades }}</p>
          <p class="text-[11px] text-gray-400">en stock</p>
        </div>
        <div class="rounded-xl p-3 bg-white border border-gray-100 shadow-sm">
          <p class="text-xs text-gray-400">{{ activeTab === 'vencidos' ? 'Valor perdido' : 'Valor en riesgo' }}</p>
          <p :class="['text-xl font-bold leading-tight', activeTab === 'vencidos' ? 'text-red-600' : 'text-orange-600']">{{ fmtBs(totalValor) }}</p>
          <p class="text-[11px] text-gray-400">a precio de compra</p>
        </div>
      </div>

      <!-- Vacío -->
      <div v-if="!lista.length"
        class="bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col items-center justify-center py-16 text-center px-4">
        <svg class="w-10 h-10 text-green-400 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
        </svg>
        <p class="text-gray-600 font-semibold text-sm">
          {{ search ? 'Sin resultados' : (activeTab === 'vencidos' ? 'No hay productos vencidos' : 'Nada por vencer') }}
        </p>
        <p class="text-gray-400 text-xs mt-1">
          <template v-if="search">Prueba con otro texto de búsqueda.</template>
          <template v-else-if="activeTab === 'vencidos'">Ningún lote con stock tiene la fecha de vencimiento pasada.</template>
          <template v-else>Ningún lote con stock vence {{ rango === null ? 'próximamente' : `en los próximos ${rango} días` }}.</template>
        </p>
        <p class="text-gray-400 text-[11px] mt-3">Solo aparecen los productos comprados con fecha de vencimiento.</p>
      </div>

      <!-- Tabla -->
      <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
          <table class="w-full text-xs min-w-[820px]">
            <thead class="bg-gray-50 text-gray-400 uppercase border-b border-gray-100">
              <tr>
                <th class="px-3 py-2.5 text-left font-semibold tracking-wide">Producto</th>
                <th class="px-3 py-2.5 text-left font-semibold tracking-wide">Lote</th>
                <th class="px-3 py-2.5 text-left font-semibold tracking-wide">Vencimiento</th>
                <th class="px-3 py-2.5 text-center font-semibold tracking-wide">Disponible</th>
                <th class="px-3 py-2.5 text-right font-semibold tracking-wide">Costo u.</th>
                <th class="px-3 py-2.5 text-right font-semibold tracking-wide">Valor</th>
                <th class="px-3 py-2.5 text-left font-semibold tracking-wide hidden lg:table-cell">Compra</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in lista" :key="l.id"
                :class="['border-t border-gray-50 transition-colors', l.dias < 0 ? 'bg-red-50/40 hover:bg-red-50' : 'hover:bg-gray-50/60']">
                <td class="px-3 py-2">
                  <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100 flex items-center justify-center">
                      <img v-if="l.producto?.imagen" :src="`/storage/${l.producto.imagen}`" class="w-full h-full object-cover">
                      <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/>
                      </svg>
                    </div>
                    <div class="min-w-0">
                      <p class="font-semibold text-gray-800 leading-tight">{{ l.producto?.nombre ?? 'Producto eliminado' }}</p>
                      <p class="text-gray-400 leading-tight" style="font-size:10px">
                        {{ l.producto?.codigo_barras ?? '—' }}<template v-if="l.producto?.categoria"> · {{ l.producto.categoria.nombre }}</template>
                      </p>
                    </div>
                  </div>
                </td>
                <td class="px-3 py-2 font-mono text-gray-600">{{ l.lote || '—' }}</td>
                <td class="px-3 py-2 whitespace-nowrap">
                  <p class="text-gray-700 font-semibold">{{ fmtFechaCorta(l.fecha_vencimiento) }}</p>
                  <span :class="['inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold', diasColor(l.dias)]">
                    {{ diasLabel(l.dias) }}
                  </span>
                </td>
                <td class="px-3 py-2 text-center">
                  <span class="font-bold text-gray-800 text-sm">{{ l.disponible }}</span>
                  <span class="text-gray-400"> / {{ l.cantidad }}</span>
                </td>
                <td class="px-3 py-2 text-right text-gray-500">{{ fmtBs(l.precio_unitario) }}</td>
                <td :class="['px-3 py-2 text-right font-bold', l.dias < 0 ? 'text-red-600' : 'text-gray-800']">{{ fmtBs(l.valor) }}</td>
                <td class="px-3 py-2 text-gray-500 hidden lg:table-cell">
                  <p class="leading-tight">#{{ l.compra_id }} · {{ fmtFechaCorta(l.fecha_compra) }}</p>
                  <p class="text-gray-400 leading-tight" style="font-size:10px">{{ l.proveedor }}</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
