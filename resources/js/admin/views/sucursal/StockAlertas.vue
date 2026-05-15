<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth.js';

const route   = useRoute();
const router  = useRouter();
const auth    = useAuthStore();
const sucId   = computed(() => route.params.sucursalId);
const sucursal = computed(() => auth.sucursales.find(s => s.id == sucId.value));

const activeTab   = computed(() => route.name === 'sucursal.stock.maximo' ? 'maximo' : 'minimo');
const bajoMinimo  = ref([]);
const sobreMaximo = ref([]);
const loading     = ref(false);

async function load() {
    if (!sucId.value) return;
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/productos/stock-alertas`);
        bajoMinimo.value  = data.bajo_minimo;
        sobreMaximo.value = data.sobre_maximo;
    } finally {
        loading.value = false;
    }
}

watch(sucId, load, { immediate: true });

function setTab(tab) {
    const name = tab === 'maximo' ? 'sucursal.stock.maximo' : 'sucursal.stock.minimo';
    router.push({ name, params: { sucursalId: sucId.value } });
}

const lista = computed(() => activeTab.value === 'minimo' ? bajoMinimo.value : sobreMaximo.value);

function badgeMinimo(p) {
    if (p.excedido) return { text: `${p.diferencia} bajo mínimo`, cls: 'bg-red-100 text-red-700' };
    return { text: 'Justo en el mínimo', cls: 'bg-yellow-100 text-yellow-700' };
}

function badgeMaximo(p) {
    if (p.excedido) return { text: `${p.diferencia} sobre máximo`, cls: 'bg-orange-100 text-orange-700' };
    return { text: 'Justo en el máximo', cls: 'bg-yellow-100 text-yellow-700' };
}

function barColor(tab, p) {
    if (tab === 'minimo') return p.excedido ? 'bg-red-400' : 'bg-yellow-400';
    return p.excedido ? 'bg-orange-400' : 'bg-yellow-400';
}

function barWidth(tab, p) {
    if (tab === 'minimo') return Math.min(p.porcentaje, 100);
    return Math.min(p.porcentaje, 200);
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-4">
      <h2 class="text-xl font-bold text-gray-800">Alertas de Stock</h2>
      <p class="text-gray-400 text-xs mt-0.5">{{ sucursal?.nombre }}</p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-4 bg-gray-100 p-1 rounded-xl w-fit">
      <button @click="setTab('minimo')"
        :class="['px-4 py-2 rounded-lg text-xs font-semibold transition-colors flex items-center gap-2',
          activeTab === 'minimo'
            ? 'bg-white text-red-600 shadow-sm'
            : 'text-gray-500 hover:text-gray-700']">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
        Stock Mínimo
        <span v-if="bajoMinimo.length"
          class="bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 leading-none min-w-[18px] text-center">
          {{ bajoMinimo.length }}
        </span>
      </button>
      <button @click="setTab('maximo')"
        :class="['px-4 py-2 rounded-lg text-xs font-semibold transition-colors flex items-center gap-2',
          activeTab === 'maximo'
            ? 'bg-white text-orange-600 shadow-sm'
            : 'text-gray-500 hover:text-gray-700']">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/>
        </svg>
        Stock Máximo
        <span v-if="sobreMaximo.length"
          class="bg-orange-500 text-white text-xs rounded-full px-1.5 py-0.5 leading-none min-w-[18px] text-center">
          {{ sobreMaximo.length }}
        </span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
    </div>

    <!-- Contenido -->
    <template v-else>

      <!-- Vacío -->
      <div v-if="lista.length === 0"
        class="bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col items-center justify-center py-16 text-center">
        <svg class="w-10 h-10 text-green-400 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
        </svg>
        <p class="text-gray-600 font-semibold text-sm">Sin alertas</p>
        <p class="text-gray-400 text-xs mt-1">
          {{ activeTab === 'minimo'
              ? 'Todos los productos están por encima del stock mínimo.'
              : 'Ningún producto supera el stock máximo.' }}
        </p>
      </div>

      <!-- Tabla -->
      <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
          <table class="w-full text-xs min-w-[680px]">
            <thead class="bg-gray-50 text-gray-400 uppercase border-b border-gray-100">
              <tr>
                <th class="px-4 py-2.5 text-left font-semibold tracking-wide">Producto</th>
                <th class="px-4 py-2.5 text-left font-semibold tracking-wide hidden md:table-cell">Categoría</th>
                <th class="px-4 py-2.5 text-center font-semibold tracking-wide">Actual</th>
                <th class="px-4 py-2.5 text-center font-semibold tracking-wide">
                  {{ activeTab === 'minimo' ? 'Mínimo' : 'Máximo' }}
                </th>
                <th class="px-4 py-2.5 text-left font-semibold tracking-wide">Nivel</th>
                <th class="px-4 py-2.5 text-center font-semibold tracking-wide">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in lista" :key="p.id"
                class="border-t border-gray-50 hover:bg-gray-50/60 transition-colors">

                <!-- Producto -->
                <td class="px-4 py-3">
                  <p class="font-semibold text-gray-800 leading-tight">{{ p.nombre }}</p>
                  <p class="text-gray-400 leading-tight" style="font-size:10px">{{ p.codigo_barras ?? '—' }}</p>
                </td>

                <!-- Categoría -->
                <td class="px-4 py-3 text-gray-500 hidden md:table-cell">
                  {{ p.categoria?.nombre ?? '—' }}
                </td>

                <!-- Stock actual -->
                <td class="px-4 py-3 text-center">
                  <span class="font-bold text-gray-800 text-sm">{{ p.stock_actual }}</span>
                </td>

                <!-- Referencia (min o max) -->
                <td class="px-4 py-3 text-center text-gray-500">
                  {{ activeTab === 'minimo' ? p.stock_minimo : p.stock_maximo }}
                </td>

                <!-- Barra de nivel -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                      <div :class="barColor(activeTab, p)"
                        class="h-full rounded-full transition-all"
                        :style="{ width: barWidth(activeTab, p) + '%' }"/>
                    </div>
                    <span class="text-gray-400 w-10 text-right shrink-0">
                      {{ activeTab === 'minimo'
                          ? Math.min(p.porcentaje, 100) + '%'
                          : p.porcentaje + '%' }}
                    </span>
                  </div>
                  <p v-if="activeTab === 'maximo' && p.porcentaje > 100"
                    class="text-orange-500 text-xs mt-0.5">
                    {{ p.porcentaje }}% del máximo
                  </p>
                </td>

                <!-- Badge estado -->
                <td class="px-4 py-3 text-center">
                  <span v-if="activeTab === 'minimo'"
                    :class="badgeMinimo(p).cls"
                    class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap">
                    {{ badgeMinimo(p).text }}
                  </span>
                  <span v-else
                    :class="badgeMaximo(p).cls"
                    class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap">
                    {{ badgeMaximo(p).text }}
                  </span>
                </td>

              </tr>
            </tbody>
          </table>
        </div>

        <!-- Resumen footer -->
        <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50 flex items-center gap-4 text-xs text-gray-500">
          <span>
            <span class="font-semibold text-gray-700">{{ lista.length }}</span> producto(s)
          </span>
          <span v-if="activeTab === 'minimo'">
            <span class="font-semibold text-red-600">
              {{ lista.filter(p => p.excedido).length }}
            </span> por debajo del mínimo ·
            <span class="font-semibold text-yellow-600">
              {{ lista.filter(p => !p.excedido).length }}
            </span> exactamente en el mínimo
          </span>
          <span v-else>
            <span class="font-semibold text-orange-600">
              {{ lista.filter(p => p.excedido).length }}
            </span> por encima del máximo ·
            <span class="font-semibold text-yellow-600">
              {{ lista.filter(p => !p.excedido).length }}
            </span> exactamente en el máximo
          </span>
        </div>
      </div>

    </template>
  </div>
</template>
