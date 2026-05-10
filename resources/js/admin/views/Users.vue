<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import DropdownMenu from '../components/DropdownMenu.vue';

const users = ref([]);
const sucursales = ref([]);
const loading = ref(true);
const search = ref('');
const showModal = ref(false);
const saving = ref(false);
const errors = ref({});
const editingId = ref(null);

const rolColors = {
    super_admin: 'bg-purple-100 text-purple-700',
    admin:       'bg-blue-100 text-blue-700',
    cajero:      'bg-gray-100 text-gray-600',
};

const rolLabel = r => ({ super_admin: 'Super Admin', admin: 'Admin', cajero: 'Cajero' }[r] ?? r);

const form = ref({ name: '', nickname: '', email: '', password: '', rol: 'cajero', sucursal_id: '' });

const filtered = computed(() =>
    users.value.filter(u =>
        u.name.toLowerCase().includes(search.value.toLowerCase()) ||
        u.email.toLowerCase().includes(search.value.toLowerCase()) ||
        (u.nickname ?? '').toLowerCase().includes(search.value.toLowerCase())
    )
);

async function load() {
    loading.value = true;
    const { data } = await axios.get('/api/admin/users');
    users.value = data.users;
    sucursales.value = data.sucursales;
    loading.value = false;
}

function openCreate() {
    editingId.value = null;
    form.value = { name: '', nickname: '', email: '', password: '', rol: 'cajero', sucursal_id: '' };
    errors.value = {};
    showModal.value = true;
}

function openEdit(u) {
    editingId.value = u.id;
    form.value = { name: u.name, nickname: u.nickname ?? '', email: u.email, password: '', rol: u.rol, sucursal_id: u.sucursal?.id ?? '' };
    errors.value = {};
    showModal.value = true;
}

async function save() {
    saving.value = true;
    errors.value = {};
    const payload = { ...form.value, sucursal_id: form.value.sucursal_id || null };
    if (editingId.value && !payload.password) delete payload.password;
    try {
        if (editingId.value) {
            const { data } = await axios.put(`/api/admin/users/${editingId.value}`, payload);
            const idx = users.value.findIndex(u => u.id === editingId.value);
            if (idx >= 0) users.value[idx] = data;
        } else {
            const { data } = await axios.post('/api/admin/users', payload);
            users.value.push(data);
        }
        showModal.value = false;
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? { general: [e.response.data.message] };
    } finally {
        saving.value = false;
    }
}

async function remove(u) {
    if (!confirm(`¿Eliminar al usuario "${u.name}"?`)) return;
    try {
        await axios.delete(`/api/admin/users/${u.id}`);
        users.value = users.value.filter(x => x.id !== u.id);
    } catch (e) {
        alert(e.response?.data?.message ?? 'Error al eliminar.');
    }
}

// Display name: nickname if set, otherwise name
const displayName = u => u.nickname ? `${u.nickname}` : u.name;
const initials = u => u.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();

onMounted(load);
</script>

<template>
  <div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Usuarios</h2>
        <p class="text-gray-500 text-sm mt-0.5">Gestión de usuarios y sus roles</p>
      </div>
      <button @click="openCreate"
        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors self-start sm:self-auto">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nuevo Usuario
      </button>
    </div>

    <!-- Search -->
    <div class="mb-4">
      <div class="relative max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
        <input v-model="search" type="text" placeholder="Buscar por nombre, nickname o email..."
          class="w-full border border-gray-200 rounded-xl pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <svg class="animate-spin w-7 h-7 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-100">
          <tr>
            <th class="px-4 py-3 text-left w-12"></th>
            <th class="px-4 py-3 text-left">Usuario</th>
            <th class="px-4 py-3 text-left hidden sm:table-cell">Email</th>
            <th class="px-4 py-3 text-center">Rol</th>
            <th class="px-4 py-3 text-left hidden lg:table-cell">Sucursal</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0">
            <td colspan="5" class="px-5 py-12 text-center text-gray-400">
              <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
              </svg>
              No hay usuarios registrados
            </td>
          </tr>
          <tr v-for="u in filtered" :key="u.id" class="border-t border-gray-50 hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3">
              <DropdownMenu>
                <button @click="openEdit(u)" class="flex items-center gap-2.5 w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z"/>
                  </svg>
                  Editar
                </button>
                <div class="border-t border-gray-100 my-1"/>
                <button @click="remove(u)" class="flex items-center gap-2.5 w-full px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                  </svg>
                  Eliminar
                </button>
              </DropdownMenu>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                  {{ initials(u) }}
                </div>
                <div>
                  <p class="font-semibold text-gray-800 leading-tight">{{ displayName(u) }}</p>
                  <p v-if="u.nickname" class="text-xs text-gray-400 leading-tight">{{ u.name }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">{{ u.email }}</td>
            <td class="px-4 py-3 text-center">
              <span :class="rolColors[u.rol] ?? 'bg-gray-100 text-gray-600'"
                    class="px-2.5 py-1 rounded-full text-xs font-semibold">
                {{ rolLabel(u.rol) }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-500 hidden lg:table-cell">{{ u.sucursal?.nombre ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <Transition name="modal">
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showModal = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
          <h3 class="font-bold text-gray-800 text-base">{{ editingId ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="save" class="px-6 py-4 space-y-4">
          <div v-if="errors.general" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
            {{ errors.general[0] }}
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nombre completo *</label>
              <input v-model="form.name" type="text" required placeholder="Juan Pérez"
                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                Nickname
                <span class="font-normal text-gray-400">(nombre corto)</span>
              </label>
              <input v-model="form.nickname" type="text" placeholder="Ej: Juancho"
                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <p v-if="errors.nickname" class="text-red-500 text-xs mt-1">{{ errors.nickname[0] }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email *</label>
            <input v-model="form.email" type="email" required placeholder="usuario@ejemplo.com"
              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email[0] }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">
              {{ editingId ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña *' }}
            </label>
            <input v-model="form.password" type="password" :required="!editingId" placeholder="Mínimo 8 caracteres"
              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p v-if="errors.password" class="text-red-500 text-xs mt-1">{{ errors.password[0] }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">Rol *</label>
              <select v-model="form.rol" required
                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="super_admin">Super Admin</option>
                <option value="admin">Admin</option>
                <option value="cajero">Cajero</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1.5">Sucursal</label>
              <select v-model="form.sucursal_id"
                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="">Sin sucursal</option>
                <option v-for="s in sucursales" :key="s.id" :value="s.id">{{ s.nombre }}</option>
              </select>
            </div>
          </div>

          <div class="flex gap-3 pt-2 pb-1">
            <button type="submit" :disabled="saving"
              class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white py-2.5 rounded-xl text-sm font-semibold transition-colors">
              {{ saving ? 'Guardando...' : (editingId ? 'Guardar cambios' : 'Crear usuario') }}
            </button>
            <button type="button" @click="showModal = false"
              class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl text-sm font-semibold transition-colors">
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
