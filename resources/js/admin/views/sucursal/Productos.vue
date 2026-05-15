<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth.js';
import DropdownMenu from '../../components/DropdownMenu.vue';

const route   = useRoute();
const auth    = useAuthStore();
const sucId   = computed(() => route.params.sucursalId);
const sucursal = computed(() => auth.sucursales.find(s => s.id == sucId.value));

const productos  = ref([]);
const categorias = ref([]);
const loading    = ref(false);
const search     = ref('');
const filterCat  = ref('');
const showModal  = ref(false);
const editingId  = ref(null);
const saving     = ref(false);
const errors     = ref({});
const imagePreview = ref(null);
const imageFile    = ref(null);
const dragOver     = ref(false);

const emptyForm = () => ({
    codigo_barras: '', nombre: '', descripcion: '', categoria_id: '',
    precio_compra: '', precio_venta: '', precio_mayoreo: '',
    stock_minimo: '', stock_maximo: '', activo: true,
});
const form = ref(emptyForm());

const filtered = computed(() => {
    let list = productos.value;
    if (filterCat.value) list = list.filter(p => p.categoria_id == filterCat.value);
    if (search.value) {
        const s = search.value.toLowerCase();
        list = list.filter(p =>
            p.nombre.toLowerCase().includes(s) ||
            (p.codigo_barras ?? '').toLowerCase().includes(s)
        );
    }
    return list;
});

const imageUrl = (p) => p.imagen ? `/storage/${p.imagen}` : null;

async function load() {
    if (!sucId.value) return;
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/productos`);
        productos.value  = data.productos;
        categorias.value = data.categorias;
    } finally {
        loading.value = false;
    }
}

watch(sucId, load, { immediate: true });

// ── Image ──────────────────────────────────────────────────────
function compressImage(file, maxW = 800, maxH = 800, quality = 0.82) {
    return new Promise(resolve => {
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            URL.revokeObjectURL(url);
            let { width, height } = img;
            const ratio = Math.min(maxW / width, maxH / height, 1);
            width  = Math.round(width  * ratio);
            height = Math.round(height * ratio);
            const canvas = document.createElement('canvas');
            canvas.width  = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(img, 0, 0, width, height);
            canvas.toBlob(blob => resolve(new File([blob], file.name, { type: 'image/jpeg' })), 'image/jpeg', quality);
        };
        img.src = url;
    });
}

async function setImageFile(file) {
    if (!file || !file.type.startsWith('image/')) return;
    const compressed   = await compressImage(file);
    imageFile.value    = compressed;
    imagePreview.value = URL.createObjectURL(compressed);
}

async function onImageChange(e) { await setImageFile(e.target.files[0]); }
async function onDrop(e) { dragOver.value = false; await setImageFile(e.dataTransfer.files[0]); }

function clearImage() {
    imageFile.value    = null;
    imagePreview.value = null;
    const inp = document.getElementById('imagen-input-s');
    if (inp) inp.value = '';
}

// ── Modal ──────────────────────────────────────────────────────
function openCreate() {
    editingId.value    = null;
    form.value         = emptyForm();
    errors.value       = {};
    imagePreview.value = null;
    imageFile.value    = null;
    showModal.value    = true;
}

function openEdit(p) {
    editingId.value = p.id;
    form.value = {
        codigo_barras:  p.codigo_barras ?? '',
        nombre:         p.nombre,
        descripcion:    p.descripcion ?? '',
        categoria_id:   p.categoria_id ?? '',
        precio_compra:  p.precio_compra,
        precio_venta:   p.precio_venta,
        precio_mayoreo: p.precio_mayoreo ?? '',
        stock_minimo:   p.stock_minimo ?? '',
        stock_maximo:   p.stock_maximo ?? '',
        activo:         p.activo ?? true,
    };
    errors.value       = {};
    imagePreview.value = imageUrl(p);
    imageFile.value    = null;
    showModal.value    = true;
}

async function save() {
    saving.value = true;
    errors.value = {};
    const fd = new FormData();
    Object.entries(form.value).forEach(([k, v]) => {
        if (k === 'activo') fd.append(k, v ? 1 : 0);
        else if (v !== '' && v !== null && v !== undefined) fd.append(k, v);
    });
    if (imageFile.value) fd.append('imagen', imageFile.value);

    const url = editingId.value
        ? `/api/admin/sucursales/${sucId.value}/productos/${editingId.value}`
        : `/api/admin/sucursales/${sucId.value}/productos`;

    try {
        const { data } = await axios.post(url, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
        if (editingId.value) {
            const idx = productos.value.findIndex(p => p.id === editingId.value);
            if (idx >= 0) productos.value[idx] = data;
        } else {
            productos.value.push(data);
        }
        showModal.value = false;
    } catch (e) {
        if (e.response?.status === 422)
            errors.value = e.response.data.errors ?? { general: [e.response.data.message] };
    } finally {
        saving.value = false;
    }
}

async function remove(p) {
    if (!confirm(`¿Eliminar "${p.nombre}"?`)) return;
    try {
        await axios.delete(`/api/admin/sucursales/${sucId.value}/productos/${p.id}`);
        productos.value = productos.value.filter(x => x.id !== p.id);
    } catch (e) {
        alert(e.response?.data?.message ?? 'Error al eliminar.');
    }
}

const fmtBs = v => v != null ? `Bs ${parseFloat(v).toFixed(2)}` : '—';
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Productos</h2>
        <p class="text-gray-400 text-xs mt-0.5">{{ filtered.length }} de {{ productos.length }} producto(s)</p>
      </div>
      <button @click="openCreate"
        class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-semibold transition-colors self-start sm:self-auto">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nuevo Producto
      </button>
    </div>

    <!-- Filters -->
    <div class="flex gap-2 mb-3 flex-wrap">
      <div class="relative">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
        <input v-model="search" type="text" placeholder="Buscar..."
          class="border border-gray-200 rounded-lg pl-8 pr-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white w-48">
      </div>
      <select v-model="filterCat"
        class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
        <option value="">Todas las categorías</option>
        <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
      </select>
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
      <div class="overflow-x-auto">
        <table class="w-full text-xs min-w-[700px]">
          <thead class="bg-gray-50 text-gray-400 uppercase border-b border-gray-100">
            <tr>
              <th class="px-3 py-2 text-left font-semibold tracking-wide w-24">Acciones</th>
              <th class="px-3 py-2 text-left w-8"></th>
              <th class="px-3 py-2 text-left font-semibold tracking-wide">Producto</th>
              <th class="px-3 py-2 text-left font-semibold tracking-wide hidden md:table-cell">Categoría</th>
              <th class="px-3 py-2 text-right font-semibold tracking-wide">P. Venta</th>
              <th class="px-3 py-2 text-right font-semibold tracking-wide hidden lg:table-cell">P. Costo</th>
              <th class="px-3 py-2 text-center font-semibold tracking-wide hidden sm:table-cell">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="7" class="px-4 py-10 text-center text-gray-400">No hay productos.</td>
            </tr>
            <tr v-for="p in filtered" :key="p.id" class="border-t border-gray-50 hover:bg-blue-50/30 transition-colors">
              <td class="px-3 py-1.5">
                <DropdownMenu>
                  <button @click="openEdit(p)"
                    class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z"/>
                    </svg>
                    Editar
                  </button>
                  <div class="border-t border-gray-100 my-0.5"/>
                  <button @click="remove(p)"
                    class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                    </svg>
                    Eliminar
                  </button>
                </DropdownMenu>
              </td>
              <td class="px-2 py-1.5">
                <div class="w-8 h-8 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0 flex items-center justify-center">
                  <img v-if="imageUrl(p)" :src="imageUrl(p)" alt="" class="w-full h-full object-cover">
                  <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                  </svg>
                </div>
              </td>
              <td class="px-3 py-1.5">
                <p class="font-semibold text-gray-800 leading-tight">{{ p.nombre }}</p>
                <p class="text-gray-400 leading-tight" style="font-size:10px">{{ p.codigo_barras ?? '—' }}</p>
              </td>
              <td class="px-3 py-1.5 text-gray-500 hidden md:table-cell">{{ p.categoria?.nombre ?? '—' }}</td>
              <td class="px-3 py-1.5 text-right font-semibold text-gray-800">{{ fmtBs(p.precio_venta) }}</td>
              <td class="px-3 py-1.5 text-right text-gray-500 hidden lg:table-cell">{{ fmtBs(p.precio_compra) }}</td>
              <td class="px-3 py-1.5 text-center hidden sm:table-cell">
                <span :class="p.activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold">
                  {{ p.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <Transition name="modal">
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="showModal = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
          <h3 class="font-bold text-gray-800 text-sm">
            {{ editingId ? 'Editar Producto' : 'Nuevo Producto' }}
            <span class="font-normal text-gray-400 text-xs ml-1">— {{ sucursal?.nombre }}</span>
          </h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="save" class="px-5 py-4 space-y-3">
          <div v-if="errors.general" class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-3 py-2">
            {{ errors.general[0] }}
          </div>

          <!-- Imagen drag & drop -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Imagen del producto</label>
            <input id="imagen-input-s" type="file" accept="image/*" @change="onImageChange" class="hidden">
            <div @click="document.getElementById('imagen-input-s').click()"
              @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="onDrop"
              :class="['flex flex-col items-center justify-center w-full rounded-xl border-2 border-dashed cursor-pointer transition-colors relative overflow-hidden select-none',
                dragOver ? 'border-blue-400 bg-blue-50' : 'border-gray-200 bg-gray-50 hover:border-blue-300 hover:bg-blue-50/40',
                imagePreview ? 'h-36' : 'h-24']">
              <img v-if="imagePreview" :src="imagePreview" alt="" class="absolute inset-0 w-full h-full object-contain p-1 pointer-events-none">
              <div v-if="imagePreview" class="absolute inset-0 bg-black/0 hover:bg-black/30 transition-colors flex items-center justify-center group">
                <span class="opacity-0 group-hover:opacity-100 text-white text-xs font-semibold">Cambiar imagen</span>
              </div>
              <template v-if="!imagePreview">
                <svg class="w-7 h-7 text-gray-300 mb-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                </svg>
                <p class="text-xs text-gray-400"><span class="text-blue-500 font-semibold">Seleccionar</span> o arrastrar imagen</p>
                <p class="text-gray-300 text-xs">JPG, PNG · máx 2 MB</p>
              </template>
            </div>
            <button v-if="imagePreview" type="button" @click="clearImage" class="mt-1 text-xs text-red-500 hover:text-red-700">Quitar imagen</button>
          </div>

          <!-- Código + Nombre -->
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Código de barras</label>
              <input v-model="form.codigo_barras" type="text" placeholder="7772106..."
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre *</label>
              <input v-model="form.nombre" type="text" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
              <p v-if="errors.nombre" class="text-red-500 text-xs mt-0.5">{{ errors.nombre[0] }}</p>
            </div>
          </div>

          <!-- Categoría -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Categoría</label>
            <select v-model="form.categoria_id"
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
              <option value="">Sin categoría</option>
              <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
          </div>

          <!-- Precios -->
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">P. Costo *</label>
              <input v-model="form.precio_compra" type="number" step="0.01" min="0" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
              <p v-if="errors.precio_compra" class="text-red-500 text-xs mt-0.5">{{ errors.precio_compra[0] }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">P. Venta *</label>
              <input v-model="form.precio_venta" type="number" step="0.01" min="0" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
              <p v-if="errors.precio_venta" class="text-red-500 text-xs mt-0.5">{{ errors.precio_venta[0] }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">P. Mayoreo</label>
              <input v-model="form.precio_mayoreo" type="number" step="0.01" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
          </div>

          <!-- Stock -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Stock Mínimo</label>
              <input v-model="form.stock_minimo" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Stock Máximo</label>
              <input v-model="form.stock_maximo" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
          </div>

          <!-- Activo -->
          <div class="flex items-center gap-2">
            <input id="activo-s" v-model="form.activo" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600">
            <label for="activo-s" class="text-xs font-semibold text-gray-600">Producto activo</label>
          </div>

          <!-- Buttons -->
          <div class="flex gap-2 pt-1">
            <button type="submit" :disabled="saving"
              class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white py-2 rounded-lg text-xs font-semibold transition-colors">
              {{ saving ? 'Guardando...' : (editingId ? 'Guardar cambios' : 'Crear producto') }}
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
