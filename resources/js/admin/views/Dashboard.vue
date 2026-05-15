<script setup>
import { ref, onMounted, computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title, Tooltip, Legend,
    BarElement, CategoryScale, LinearScale,
} from 'chart.js';
import axios from 'axios';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const loading = ref(true);
const data = ref(null);

const chartData = computed(() => ({
    labels: data.value?.ventas_semana?.labels ?? [],
    datasets: [{
        label: 'Ventas (Bs)',
        data: data.value?.ventas_semana?.data ?? [],
        backgroundColor: '#3b82f6',
        borderRadius: 6,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { grid: { color: '#f1f5f9' }, ticks: { color: '#64748b' } },
        x: { grid: { display: false }, ticks: { color: '#64748b' } },
    },
};

function fmt(n) {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2 }).format(n ?? 0);
}

onMounted(async () => {
    const res = await axios.get('/api/admin/dashboard');
    data.value = res.data;
    loading.value = false;
});
</script>

<template>
  <div>

    <!-- Page title -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
      <p class="text-gray-500 text-sm mt-1">Resumen general de todas las sucursales</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-64">
      <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
    </div>

    <template v-else>
      <!-- Stat cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="rounded-2xl p-5 text-white" style="background:#7c3aed">
          <p class="text-sm font-medium opacity-80">Sucursales Activas</p>
          <p class="text-3xl font-bold mt-2">{{ data.sucursales_activas }}</p>
          <p class="text-xs opacity-70 mt-1">de {{ data.sucursales_total }} totales</p>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:#ea580c">
          <p class="text-sm font-medium opacity-80">Usuarios</p>
          <p class="text-3xl font-bold mt-2">{{ data.usuarios_total }}</p>
          <p class="text-xs opacity-70 mt-1">registrados en el sistema</p>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:#16a34a">
          <p class="text-sm font-medium opacity-80">Ventas Hoy</p>
          <p class="text-2xl font-bold mt-2">{{ fmt(data.ventas_hoy) }} Bs</p>
          <p class="text-xs opacity-70 mt-1">todas las sucursales</p>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:#0f766e">
          <p class="text-sm font-medium opacity-80">Ventas del Mes</p>
          <p class="text-2xl font-bold mt-2">{{ fmt(data.ventas_mes) }} Bs</p>
          <p class="text-xs opacity-70 mt-1">mes en curso</p>
        </div>

      </div>

      <!-- Chart + Sucursales table -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Bar chart -->
        <div class="bg-white rounded-2xl p-5 shadow-sm">
          <h3 class="font-semibold text-gray-700 mb-1">Ventas últimos 7 días</h3>
          <p class="text-gray-400 text-xs mb-4">Suma total por día (todas las sucursales)</p>
          <div class="h-56">
            <Bar :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Sucursales table -->
        <div class="bg-white rounded-2xl p-5 shadow-sm">
          <h3 class="font-semibold text-gray-700 mb-4">Estado de Sucursales</h3>
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-400 text-xs uppercase border-b border-gray-100">
                <th class="pb-2">Sucursal</th>
                <th class="pb-2 text-right">Hoy</th>
                <th class="pb-2 text-right">Productos</th>
                <th class="pb-2 text-center">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in data.sucursales" :key="s.id" class="border-b border-gray-50 hover:bg-gray-50">
                <td class="py-2 font-medium text-gray-700">{{ s.nombre }}</td>
                <td class="py-2 text-right text-gray-600">{{ fmt(s.ventas_hoy) }}</td>
                <td class="py-2 text-right text-gray-600">
                  {{ s.total_productos }}
                  <span v-if="s.bajo_stock" class="ml-1 text-orange-500 text-xs">({{ s.bajo_stock }} bajo)</span>
                </td>
                <td class="py-2 text-center">
                  <span :class="s.activa ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                        class="px-2 py-0.5 rounded-full text-xs font-medium">
                    {{ s.activa ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </template>

  </div>
</template>
