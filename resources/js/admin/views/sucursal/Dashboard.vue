<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth.js';
import EstadisticasPanel from '../../components/EstadisticasPanel.vue';

const route    = useRoute();
const auth     = useAuthStore();
const sucId    = computed(() => Number(route.params.sucursalId));
const sucursal = computed(() => auth.sucursales.find(s => s.id == sucId.value));
</script>

<template>
  <div>
    <div class="mb-2 flex items-baseline gap-2">
      <h2 class="text-base font-bold text-gray-800">Dashboard</h2>
      <p class="text-gray-400 text-xs">{{ sucursal?.nombre }} — ventas, ganancia, productos y vendedores</p>
    </div>
    <EstadisticasPanel :sucursal-id="sucId" />
  </div>
</template>
