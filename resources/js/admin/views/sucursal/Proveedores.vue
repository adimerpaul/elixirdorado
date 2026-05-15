<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth.js';

const route    = useRoute();
const auth     = useAuthStore();
const sucId    = computed(() => route.params.sucursalId);
const sucursal = computed(() => auth.sucursales.find(s => s.id == sucId.value));

// ── Lista ─────────────────────────────────────────────────────
const proveedores = ref([]);
const loading     = ref(false);

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/proveedores?todos=1`);
        proveedores.value = data;
    } finally {
        loading.value = false;
    }
}

// ── Modal ─────────────────────────────────────────────────────
const showModal = ref(false);
const saving    = ref(false);
const errores   = ref({});
const form      = ref({ id: null, nombre: '', telefono: '', email: '', activo: true });

function abrirNuevo() {
    form.value  = { id: null, nombre: '', telefono: '', email: '', activo: true };
    errores.value = {};
    showModal.value = true;
}

function abrirEditar(p) {
    form.value  = { id: p.id, nombre: p.nombre, telefono: p.telefono ?? '', email: p.email ?? '', activo: p.activo };
    errores.value = {};
    showModal.value = true;
}

async function guardar() {
    errores.value = {};
    saving.value  = true;
    try {
        if (form.value.id) {
            const { data } = await axios.put(
                `/api/admin/sucursales/${sucId.value}/proveedores/${form.value.id}`,
                form.value
            );
            const idx = proveedores.value.findIndex(p => p.id === data.id);
            if (idx !== -1) proveedores.value[idx] = data;
        } else {
            const { data } = await axios.post(
                `/api/admin/sucursales/${sucId.value}/proveedores`,
                form.value
            );
            proveedores.value.push(data);
        }
        showModal.value = false;
    } catch (e) {
        if (e.response?.status === 422)
            errores.value = e.response.data.errors ?? {};
        else
            alert(e.response?.data?.message ?? 'Error al guardar.');
    } finally {
        saving.value = false;
    }
}

async function eliminar(p) {
    if (!confirm(`¿Eliminar el proveedor "${p.nombre}"? Esta acción no se puede deshacer.`)) return;
    try {
        await axios.delete(`/api/admin/sucursales/${sucId.value}/proveedores/${p.id}`);
        proveedores.value = proveedores.value.filter(x => x.id !== p.id);
    } catch (e) {
        alert(e.response?.data?.message ?? 'Error al eliminar.');
    }
}

// ── Watch ─────────────────────────────────────────────────────
watch(sucId, load, { immediate: true });
</script>

<template>
  <div class="flex flex-col gap-4">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Proveedores</h2>
        <p class="text-gray-400 text-xs mt-0.5">{{ sucursal?.nombre }}</p>
      </div>
      <button @click="abrirNuevo"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nuevo proveedor
      </button>
    </div>

    <!-- Tabla -->
    <div v-if="loading" class="flex justify-center py-16">
      <svg class="animate-spin w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
    </div>

    <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100 text-gray-400 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left font-semibold tracking-wide">Nombre</th>
            <th class="px-4 py-3 text-left font-semibold tracking-wide">Teléfono</th>
            <th class="px-4 py-3 text-left font-semibold tracking-wide">Email</th>
            <th class="px-4 py-3 text-center font-semibold tracking-wide w-24">Estado</th>
            <th class="px-4 py-3 w-28"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!proveedores.length">
            <td colspan="5" class="py-12 text-center text-gray-400 text-sm">
              No hay proveedores registrados.
            </td>
          </tr>
          <tr v-for="p in proveedores" :key="p.id"
            class="border-t border-gray-50 hover:bg-gray-50/60 transition-colors">
            <td class="px-4 py-3 font-semibold text-gray-800">{{ p.nombre }}</td>
            <td class="px-4 py-3 text-gray-500">{{ p.telefono || '—' }}</td>
            <td class="px-4 py-3 text-gray-500">{{ p.email || '—' }}</td>
            <td class="px-4 py-3 text-center">
              <span :class="p.activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold">
                {{ p.activo ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1">
                <button @click="abrirEditar(p)"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                  </svg>
                </button>
                <button @click="eliminar(p)"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>

  <!-- ── Modal ───────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="showModal"
      class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="showModal = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">

        <div class="flex items-center justify-between mb-5">
          <h3 class="text-base font-bold text-gray-800">
            {{ form.id ? 'Editar proveedor' : 'Nuevo proveedor' }}
          </h3>
          <button @click="showModal = false"
            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre *</label>
            <input v-model="form.nombre" type="text" placeholder="Nombre del proveedor"
              :class="['w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
                errores.nombre ? 'border-red-400' : 'border-gray-200']"
              @keyup.enter="guardar">
            <p v-if="errores.nombre" class="text-red-500 text-xs mt-0.5">
              {{ Array.isArray(errores.nombre) ? errores.nombre[0] : errores.nombre }}
            </p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Teléfono</label>
            <input v-model="form.telefono" type="text" placeholder="Ej: 71234567"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
            <input v-model="form.email" type="email" placeholder="correo@ejemplo.com"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div v-if="form.id" class="flex items-center gap-3 pt-1">
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="form.activo" class="sr-only peer">
              <div class="w-10 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer
                peer-checked:bg-blue-600 transition-colors after:content-[''] after:absolute after:top-0.5
                after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all
                peer-checked:after:translate-x-5"></div>
            </label>
            <span class="text-sm text-gray-600 font-medium">{{ form.activo ? 'Activo' : 'Inactivo' }}</span>
          </div>
        </div>

        <div class="flex gap-2 mt-5">
          <button @click="showModal = false"
            class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors">
            Cancelar
          </button>
          <button @click="guardar" :disabled="saving"
            class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-2 rounded-xl text-sm font-bold transition-colors">
            {{ saving ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>
