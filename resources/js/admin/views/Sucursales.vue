<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import DropdownMenu from '../components/DropdownMenu.vue';

const sucursales = ref([]);
const loading    = ref(true);
const search     = ref('');
const showModal  = ref(false);
const saving     = ref(false);
const errors     = ref({});
const editingId  = ref(null);

const form = ref({
    nombre: '', slug: '', direccion: '', telefono: '', email: '',
    admin_nombre: '', admin_email: '', admin_password: '',
});

const filtered = computed(() =>
    sucursales.value.filter(s =>
        s.nombre.toLowerCase().includes(search.value.toLowerCase()) ||
        s.slug.toLowerCase().includes(search.value.toLowerCase())
    )
);

async function load() {
    loading.value = true;
    const { data } = await axios.get('/api/admin/sucursales');
    sucursales.value = data;
    loading.value = false;
}

function openCreate() {
    editingId.value = null;
    form.value = { nombre: '', slug: '', direccion: '', telefono: '', email: '', admin_nombre: '', admin_email: '', admin_password: '' };
    errors.value = {};
    showModal.value = true;
}

function openEdit(s) {
    editingId.value = s.id;
    form.value = { nombre: s.nombre, slug: s.slug, direccion: s.direccion ?? '', telefono: s.telefono ?? '', email: s.email ?? '', admin_nombre: '', admin_email: '', admin_password: '' };
    errors.value = {};
    showModal.value = true;
}

async function save() {
    saving.value = true;
    errors.value = {};
    try {
        if (editingId.value) {
            const { data } = await axios.put(`/api/admin/sucursales/${editingId.value}`, form.value);
            const idx = sucursales.value.findIndex(s => s.id === editingId.value);
            if (idx >= 0) sucursales.value.splice(idx, 1, data);
        } else {
            const { data } = await axios.post('/api/admin/sucursales', form.value);
            sucursales.value.push(data);
        }
        showModal.value = false;
    } catch (e) {
        if (e.response?.status === 422)
            errors.value = e.response.data.errors ?? { general: [e.response.data.message] };
        else
            errors.value = { general: [e.response?.data?.message ?? 'Error al guardar.'] };
    } finally {
        saving.value = false;
    }
}

async function toggle(s) {
    try {
        const { data } = await axios.patch(`/api/admin/sucursales/${s.id}/toggle`);
        const idx = sucursales.value.findIndex(x => x.id === s.id);
        if (idx >= 0) sucursales.value.splice(idx, 1, data);
    } catch (e) {
        alert(e.response?.data?.message ?? 'Error al cambiar estado.');
    }
}

async function remove(s) {
    if (!confirm(`¿Eliminar la sucursal "${s.nombre}"?\n\nLos datos se conservan (soft delete).`)) return;
    try {
        await axios.delete(`/api/admin/sucursales/${s.id}`);
        sucursales.value = sucursales.value.filter(x => x.id !== s.id);
    } catch (e) {
        alert(e.response?.data?.message ?? 'Error al eliminar.');
    }
}

onMounted(load);
</script>

<template>
  <div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Sucursales</h2>
        <p class="text-gray-400 text-xs mt-0.5">{{ sucursales.length }} sucursal(es) registrada(s)</p>
      </div>
      <div class="flex gap-2 self-start sm:self-auto">
        <button @click="load"
          class="flex items-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
          </svg>
          Actualizar
        </button>
        <button @click="openCreate"
          class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Nueva Sucursal
        </button>
      </div>
    </div>

    <!-- Search -->
    <div class="mb-3">
      <div class="relative max-w-xs">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
        <input v-model="search" type="text" placeholder="Buscar..."
          class="w-full border border-gray-200 rounded-lg pl-8 pr-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
      <table class="w-full text-xs">
        <thead class="bg-gray-50 text-gray-400 uppercase border-b border-gray-100">
          <tr>
            <th class="px-3 py-2 text-left font-semibold tracking-wide w-28">Acciones</th>
            <th class="px-3 py-2 text-left font-semibold tracking-wide">Nombre</th>
            <th class="px-3 py-2 text-left font-semibold tracking-wide hidden sm:table-cell">Slug</th>
            <th class="px-3 py-2 text-left font-semibold tracking-wide hidden lg:table-cell">Base de Datos</th>
            <th class="px-3 py-2 text-left font-semibold tracking-wide hidden md:table-cell">Teléfono</th>
            <th class="px-3 py-2 text-center font-semibold tracking-wide">Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0">
            <td colspan="6" class="px-4 py-10 text-center text-gray-400 text-xs">No hay sucursales registradas.</td>
          </tr>
          <tr v-for="s in filtered" :key="s.id"
              class="border-t border-gray-50 hover:bg-blue-50/30 transition-colors">
            <!-- Dropdown acciones -->
            <td class="px-3 py-1.5">
              <DropdownMenu>
                <button @click="openEdit(s)"
                  class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z"/>
                  </svg>
                  Editar
                </button>
                <button @click="toggle(s)"
                  class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9"/>
                  </svg>
                  {{ s.activa ? 'Desactivar' : 'Activar' }}
                </button>
                <div class="border-t border-gray-100 my-0.5"/>
                <button @click="remove(s)"
                  class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                  </svg>
                  Eliminar
                </button>
              </DropdownMenu>
            </td>
            <td class="px-3 py-1.5 font-semibold text-gray-800">{{ s.nombre }}</td>
            <td class="px-3 py-1.5 text-gray-500 hidden sm:table-cell">{{ s.slug }}</td>
            <td class="px-3 py-1.5 text-gray-400 font-mono hidden lg:table-cell">{{ s.base_datos }}</td>
            <td class="px-3 py-1.5 text-gray-500 hidden md:table-cell">{{ s.telefono ?? '—' }}</td>
            <td class="px-3 py-1.5 text-center">
              <span :class="s.activa
                  ? 'bg-green-100 text-green-700'
                  : 'bg-gray-100 text-gray-500'"
                class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold">
                {{ s.activa ? 'Activa' : 'Inactiva' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <Transition name="modal">
    <div v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="showModal = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
          <h3 class="font-bold text-gray-800 text-sm">{{ editingId ? 'Editar Sucursal' : 'Nueva Sucursal' }}</h3>
          <button @click="showModal = false"
            class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="save" class="px-5 py-4 space-y-3">

          <div v-if="errors.general"
            class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-3 py-2">
            {{ errors.general[0] }}
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre *</label>
              <input v-model="form.nombre" type="text" required placeholder="Ej: Sucursal Centro"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
              <p v-if="errors.nombre" class="text-red-500 text-xs mt-0.5">{{ errors.nombre[0] }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">
                Slug *
                <span v-if="editingId" class="font-normal text-gray-400">(fijo)</span>
              </label>
              <input v-model="form.slug" type="text" :disabled="!!editingId" required placeholder="centro"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed">
              <p v-if="errors.slug" class="text-red-500 text-xs mt-0.5">{{ errors.slug[0] }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Teléfono</label>
              <input v-model="form.telefono" type="text" placeholder="78901234"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
              <input v-model="form.email" type="email" placeholder="sucursal@ejemplo.com"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Dirección</label>
            <input v-model="form.direccion" type="text" placeholder="Av. Arce 123, La Paz"
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>

          <template v-if="!editingId">
            <div class="pt-1 border-t border-gray-100">
              <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">Administrador de la sucursal</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre completo *</label>
                <input v-model="form.admin_nombre" type="text" required placeholder="Juan Pérez"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p v-if="errors.admin_nombre" class="text-red-500 text-xs mt-0.5">{{ errors.admin_nombre[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Email *</label>
                <input v-model="form.admin_email" type="email" required placeholder="admin@ejemplo.com"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p v-if="errors.admin_email" class="text-red-500 text-xs mt-0.5">{{ errors.admin_email[0] }}</p>
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Contraseña *</label>
              <input v-model="form.admin_password" type="password" required placeholder="Mínimo 8 caracteres"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
              <p v-if="errors.admin_password" class="text-red-500 text-xs mt-0.5">{{ errors.admin_password[0] }}</p>
            </div>
          </template>

          <div class="flex gap-2 pt-1">
            <button type="submit" :disabled="saving"
              class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white py-2 rounded-lg text-xs font-semibold transition-colors">
              {{ saving ? 'Guardando...' : (editingId ? 'Guardar cambios' : 'Crear sucursal') }}
            </button>
            <button type="button" @click="showModal = false"
              class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg text-xs font-semibold transition-colors">
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
    </Transition>

  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
