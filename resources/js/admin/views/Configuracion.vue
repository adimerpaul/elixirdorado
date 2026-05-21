<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const form   = ref({ whatsapp: '', nombre_negocio: '' });
const saving = ref(false);
const saved  = ref(false);
const error  = ref('');

onMounted(async () => {
    const { data } = await axios.get('/api/admin/configuracion');
    form.value.whatsapp       = data.whatsapp       ?? '';
    form.value.nombre_negocio = data.nombre_negocio ?? '';
});

async function guardar() {
    saving.value = true;
    saved.value  = false;
    error.value  = '';
    try {
        await axios.post('/api/admin/configuracion', form.value);
        saved.value = true;
        setTimeout(() => { saved.value = false; }, 3000);
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Error al guardar.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
  <div class="max-w-xl">
    <div class="mb-5">
      <h2 class="text-xl font-bold text-gray-800">Configuración</h2>
      <p class="text-gray-400 text-xs mt-0.5">Ajustes generales del sistema</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">

      <!-- WhatsApp -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">
          Número WhatsApp
          <span class="text-gray-400 font-normal text-xs ml-1">(pedidos públicos)</span>
        </label>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-500 font-mono bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">+</span>
          <input v-model="form.whatsapp" type="text" placeholder="59168289548"
            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono">
        </div>
        <p class="text-xs text-gray-400 mt-1">
          Formato internacional sin el "+": <span class="font-mono">59168289548</span>
          (591 = Bolivia + número de 8 dígitos)
        </p>
      </div>

      <!-- Nombre negocio -->
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre del negocio</label>
        <input v-model="form.nombre_negocio" type="text" placeholder="Elixir Dorado"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
      </div>

      <!-- Alerta error -->
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-3 py-2">
        {{ error }}
      </div>

      <!-- Botón -->
      <div class="flex items-center gap-3 pt-1">
        <button @click="guardar" :disabled="saving"
          class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2">
          <svg v-if="!saving" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
          </svg>
          <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          {{ saving ? 'Guardando...' : 'Guardar cambios' }}
        </button>
        <Transition name="fade">
          <span v-if="saved" class="text-emerald-600 text-sm font-semibold flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
            Guardado
          </span>
        </Transition>
      </div>
    </div>

    <!-- Preview enlace WA -->
    <div v-if="form.whatsapp" class="mt-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
      <p class="text-xs text-green-700 font-semibold mb-1">Enlace WhatsApp generado:</p>
      <a :href="`https://wa.me/${form.whatsapp}`" target="_blank" rel="noopener"
        class="text-sm text-green-800 font-mono break-all hover:underline">
        https://wa.me/{{ form.whatsapp }}
      </a>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
