<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import DropdownMenu from '../components/DropdownMenu.vue';

const users      = ref([]);
const sucursales = ref([]);
const loading    = ref(true);
const search     = ref('');
const showModal  = ref(false);
const saving     = ref(false);
const errors     = ref({});
const editingId  = ref(null);

const rolColors = {
    super_admin: 'bg-purple-100 text-purple-700',
    admin:       'bg-blue-100 text-blue-700',
    cajero:      'bg-gray-100 text-gray-600',
};
const rolLabel = r => ({ super_admin: 'Super Admin', admin: 'Admin', cajero: 'Cajero' }[r] ?? r);

const emptyForm = () => ({
    name: '', nickname: '', email: '', password: '',
    rol: 'cajero', sucursal_id: '',
    permisos: [],
});
const form = ref(emptyForm());

const filtered = computed(() =>
    users.value.filter(u =>
        u.name.toLowerCase().includes(search.value.toLowerCase()) ||
        u.email.toLowerCase().includes(search.value.toLowerCase()) ||
        (u.nickname ?? '').toLowerCase().includes(search.value.toLowerCase())
    )
);

const esSuperAdmin = computed(() => form.value.rol === 'super_admin');

// Toggle a permission in the form array
function togglePerm(name) {
    const idx = form.value.permisos.indexOf(name);
    if (idx >= 0) form.value.permisos.splice(idx, 1);
    else form.value.permisos.push(name);
}
function hasPerm(name) {
    return form.value.permisos.includes(name);
}

async function load() {
    loading.value = true;
    const { data } = await axios.get('/api/admin/users');
    users.value      = data.users;
    sucursales.value = data.sucursales;
    loading.value    = false;
}

function openCreate() {
    editingId.value = null;
    form.value      = emptyForm();
    errors.value    = {};
    showModal.value = true;
}

function openEdit(u) {
    editingId.value = u.id;
    form.value = {
        name:        u.name,
        nickname:    u.nickname ?? '',
        email:       u.email,
        password:    '',
        rol:         u.rol,
        sucursal_id: u.sucursal?.id ?? '',
        permisos:    u.permisos ? [...u.permisos] : [],
    };
    errors.value    = {};
    showModal.value = true;
}

async function save() {
    saving.value = true;
    errors.value = {};
    const payload = {
        ...form.value,
        sucursal_id: form.value.sucursal_id || null,
        permisos:    esSuperAdmin.value ? [] : form.value.permisos,
    };
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
        if (e.response?.status === 422)
            errors.value = e.response.data.errors ?? { general: [e.response.data.message] };
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

const displayName = u => u.nickname || u.name;
const initials    = u => u.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();

const permLabels = {
    dashboard:  'Dashboard',
    sucursales: 'Sucursales',
    usuarios:   'Usuarios',
};
function permLabel(name) {
    if (permLabels[name]) return permLabels[name];
    if (name.startsWith('sucursal.')) {
        const id = name.split('.')[1];
        const s  = sucursales.value.find(s => s.id == id);
        return s ? s.nombre : name;
    }
    return name;
}

onMounted(load);
</script>

<template>
  <div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Usuarios</h2>
        <p class="text-gray-400 text-xs mt-0.5">{{ users.length }} usuario(s) registrado(s)</p>
      </div>
      <button @click="openCreate"
        class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-semibold transition-colors self-start sm:self-auto">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nuevo Usuario
      </button>
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
            <th class="px-3 py-2 text-left font-semibold tracking-wide">Usuario</th>
            <th class="px-3 py-2 text-left font-semibold tracking-wide hidden sm:table-cell">Email</th>
            <th class="px-3 py-2 text-center font-semibold tracking-wide">Rol</th>
            <th class="px-3 py-2 text-left font-semibold tracking-wide hidden lg:table-cell">Sucursal</th>
            <th class="px-3 py-2 text-left font-semibold tracking-wide">Permisos</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0">
            <td colspan="6" class="px-4 py-10 text-center text-gray-400 text-xs">No hay usuarios registrados.</td>
          </tr>
          <tr v-for="u in filtered" :key="u.id" class="border-t border-gray-50 hover:bg-blue-50/30 transition-colors">
            <td class="px-3 py-1.5">
              <DropdownMenu>
                <button @click="openEdit(u)"
                  class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                  <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z"/>
                  </svg>
                  Editar
                </button>
                <div class="border-t border-gray-100 my-0.5"/>
                <button @click="remove(u)"
                  class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                  </svg>
                  Eliminar
                </button>
              </DropdownMenu>
            </td>
            <td class="px-3 py-1.5">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                  {{ initials(u) }}
                </div>
                <div>
                  <p class="font-semibold text-gray-800 leading-tight">{{ displayName(u) }}</p>
                  <p v-if="u.nickname" class="text-gray-400 leading-tight" style="font-size:10px">{{ u.name }}</p>
                </div>
              </div>
            </td>
            <td class="px-3 py-1.5 text-gray-500 hidden sm:table-cell">{{ u.email }}</td>
            <td class="px-3 py-1.5 text-center">
              <span :class="rolColors[u.rol] ?? 'bg-gray-100 text-gray-600'"
                class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold">
                {{ rolLabel(u.rol) }}
              </span>
            </td>
            <td class="px-3 py-1.5 text-gray-500 hidden lg:table-cell">{{ u.sucursal?.nombre ?? '—' }}</td>
            <td class="px-3 py-1.5">
              <div v-if="u.rol === 'super_admin'" class="flex flex-wrap gap-1">
                <span class="px-1.5 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-700">Todo</span>
              </div>
              <div v-else-if="!u.permisos?.length" class="text-gray-400 text-xs">Sin permisos</div>
              <div v-else class="flex flex-wrap gap-1">
                <span v-for="p in u.permisos" :key="p"
                  :class="['px-1.5 py-0.5 rounded text-xs font-semibold',
                    p.startsWith('sucursal.') ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700']">
                  {{ permLabel(p) }}
                </span>
              </div>
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
          <h3 class="font-bold text-gray-800 text-sm">{{ editingId ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
          <button @click="showModal = false"
            class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="save" class="px-5 py-4 space-y-4">

          <div v-if="errors.general"
            class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-3 py-2">
            {{ errors.general[0] }}
          </div>

          <!-- Nombre + Nickname -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre completo *</label>
              <input v-model="form.name" type="text" required placeholder="Juan Pérez"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
              <p v-if="errors.name" class="text-red-500 text-xs mt-0.5">{{ errors.name[0] }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Nickname</label>
              <input v-model="form.nickname" type="text" placeholder="Ej: Juancho"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Email *</label>
            <input v-model="form.email" type="email" required placeholder="usuario@ejemplo.com"
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p v-if="errors.email" class="text-red-500 text-xs mt-0.5">{{ errors.email[0] }}</p>
          </div>

          <!-- Contraseña -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">
              {{ editingId ? 'Nueva contraseña (vacío = sin cambios)' : 'Contraseña *' }}
            </label>
            <input v-model="form.password" type="password" :required="!editingId" placeholder="Mínimo 8 caracteres"
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p v-if="errors.password" class="text-red-500 text-xs mt-0.5">{{ errors.password[0] }}</p>
          </div>

          <!-- Rol + Sucursal -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Rol *</label>
              <select v-model="form.rol" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="super_admin">Super Admin</option>
                <option value="admin">Admin</option>
                <option value="cajero">Cajero</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Sucursal</label>
              <select v-model="form.sucursal_id"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="">Sin sucursal</option>
                <option v-for="s in sucursales" :key="s.id" :value="s.id">{{ s.nombre }}</option>
              </select>
            </div>
          </div>

          <!-- PERMISOS -->
          <div class="border border-gray-100 rounded-xl p-3 space-y-3">
            <div class="flex items-center justify-between">
              <p class="text-xs font-bold text-gray-700 uppercase tracking-wide">Permisos</p>
              <span v-if="esSuperAdmin" class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-semibold">
                Super Admin — acceso total
              </span>
            </div>

            <div :class="esSuperAdmin ? 'opacity-40 pointer-events-none select-none' : ''">

              <!-- Módulos globales -->
              <div class="mb-3">
                <p class="text-xs font-semibold text-gray-500 mb-1.5">Módulos del sistema</p>
                <div class="space-y-1.5">
                  <div v-for="p in [
                      { name: 'dashboard',  label: 'Dashboard' },
                      { name: 'sucursales', label: 'Gestión de Sucursales' },
                      { name: 'usuarios',   label: 'Gestión de Usuarios' },
                    ]" :key="p.name"
                    class="flex items-center gap-2 cursor-pointer group"
                    @click="togglePerm(p.name)">
                    <div :class="['w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors',
                      hasPerm(p.name) ? 'bg-blue-600 border-blue-600' : 'border-gray-300 group-hover:border-blue-400']">
                      <svg v-if="hasPerm(p.name)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                      </svg>
                    </div>
                    <span class="text-xs text-gray-700 select-none">{{ p.label }}</span>
                  </div>
                </div>
              </div>

              <!-- Acceso a sucursales -->
              <div v-if="sucursales.length">
                <p class="text-xs font-semibold text-gray-500 mb-1.5">Acceso a sucursales</p>
                <div class="space-y-1.5">
                  <div v-for="s in sucursales" :key="s.id"
                    class="flex items-center gap-2 cursor-pointer group"
                    @click="togglePerm(`sucursal.${s.id}`)">
                    <div :class="['w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors',
                      hasPerm(`sucursal.${s.id}`) ? 'bg-amber-500 border-amber-500' : 'border-gray-300 group-hover:border-amber-400']">
                      <svg v-if="hasPerm(`sucursal.${s.id}`)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                      </svg>
                    </div>
                    <span class="text-xs text-gray-700 select-none">{{ s.nombre }}</span>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Buttons -->
          <div class="flex gap-2 pt-1">
            <button type="submit" :disabled="saving"
              class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white py-2 rounded-lg text-xs font-semibold transition-colors">
              {{ saving ? 'Guardando...' : (editingId ? 'Guardar cambios' : 'Crear usuario') }}
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
