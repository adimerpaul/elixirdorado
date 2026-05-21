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

// ── Historial ──────────────────────────────────────────────────
const showHistorial   = ref(false);
const historialTab    = ref('compras');
const historialData   = ref({ producto: null, compras: [], ventas: [] });
const loadingHistorial = ref(false);

async function verHistorial(p) {
    showHistorial.value   = true;
    historialTab.value    = 'compras';
    historialData.value   = { producto: p, compras: [], ventas: [] };
    loadingHistorial.value = true;
    try {
        const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/productos/${p.id}/historial`);
        historialData.value = data;
    } finally {
        loadingHistorial.value = false;
    }
}
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

const exportUrl = (format, scope) =>
    sucId.value
        ? `/api/admin/sucursales/${sucId.value}/productos/export/${format}?scope=${scope}`
        : '#';

async function load() {
    if (!sucId.value) return;
    loading.value = true;
    try {
        const [prodRes] = await Promise.all([
            axios.get(`/api/admin/sucursales/${sucId.value}/productos`),
            loadSixpacks(),
        ]);
        productos.value  = prodRes.data.productos;
        categorias.value = prodRes.data.categorias;
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

const fmtBs    = v => v != null ? `Bs ${parseFloat(v).toFixed(2)}` : '—';
const fmtFecha = d => d ? new Date(d).toLocaleString('es-BO') : '—';
const pagoLabel = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', transferencia: 'Transferencia' };

// ── SIXPACKS ─────────────────────────────────────────────────────
const sixpacks           = ref([]);
const showSixpackModal   = ref(false);
const editingSixpackId   = ref(null);
const savingSixpack      = ref(false);
const sixpackErrors      = ref({});
const sixpackImgPreview  = ref(null);
const sixpackImgFile     = ref(null);
const sixpackDragOver    = ref(false);

const emptySixpackForm = () => ({
    codigo_barras: '', nombre: '', categoria_id: '',
    precio_compra: '', precio_venta: '', precio_mayoreo: '0',
    stock_minimo: 1, stock_maximo: 100, activo: true,
    componentes: [{ producto_id: '', cantidad: 1 }],
});
const sixpackForm = ref(emptySixpackForm());

async function loadSixpacks() {
    if (!sucId.value) return;
    const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/sixpacks`);
    sixpacks.value = data.sixpacks;
}

function openCreateSixpack() {
    editingSixpackId.value = null;
    sixpackForm.value      = emptySixpackForm();
    sixpackErrors.value    = {};
    sixpackImgPreview.value = null;
    sixpackImgFile.value    = null;
    showSixpackModal.value  = true;
}

function openEditSixpack(sp) {
    editingSixpackId.value = sp.id;
    sixpackForm.value = {
        codigo_barras:  sp.codigo_barras ?? '',
        nombre:         sp.nombre,
        categoria_id:   sp.categoria_id ?? '',
        precio_compra:  sp.precio_compra,
        precio_venta:   sp.precio_venta,
        precio_mayoreo: sp.precio_mayoreo ?? '0',
        stock_minimo:   sp.stock_minimo ?? 1,
        stock_maximo:   sp.stock_maximo ?? 100,
        activo:         !!sp.activo,
        componentes:    (sp.componentes || []).map(c => ({ producto_id: c.producto_id, cantidad: c.cantidad })),
    };
    if (!sixpackForm.value.componentes.length) sixpackForm.value.componentes.push({ producto_id: '', cantidad: 1 });
    sixpackErrors.value    = {};
    sixpackImgPreview.value = sp.imagen ? `/storage/${sp.imagen}` : null;
    sixpackImgFile.value    = null;
    showSixpackModal.value  = true;
}

function addComponente() { sixpackForm.value.componentes.push({ producto_id: '', cantidad: 1 }); }
function removeComponente(i) { if (sixpackForm.value.componentes.length > 1) sixpackForm.value.componentes.splice(i, 1); }

async function onSixpackImg(e) {
    const file = e.target.files[0];
    if (!file) return;
    const compressed      = await compressImage(file);
    sixpackImgFile.value  = compressed;
    sixpackImgPreview.value = URL.createObjectURL(compressed);
}

async function onSixpackDrop(e) {
    sixpackDragOver.value = false;
    const file = e.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const compressed      = await compressImage(file);
    sixpackImgFile.value  = compressed;
    sixpackImgPreview.value = URL.createObjectURL(compressed);
}

async function saveSixpack() {
    savingSixpack.value = true;
    sixpackErrors.value = {};
    const fd = new FormData();
    const f  = sixpackForm.value;
    ['codigo_barras','nombre','categoria_id','precio_compra','precio_venta','precio_mayoreo','stock_minimo','stock_maximo'].forEach(k => {
        if (f[k] !== '' && f[k] !== null && f[k] !== undefined) fd.append(k, f[k]);
    });
    fd.append('activo', f.activo ? 1 : 0);
    f.componentes.forEach((c, i) => {
        fd.append(`componentes[${i}][producto_id]`, c.producto_id);
        fd.append(`componentes[${i}][cantidad]`,    c.cantidad);
    });
    if (sixpackImgFile.value) fd.append('imagen', sixpackImgFile.value);

    const url = editingSixpackId.value
        ? `/api/admin/sucursales/${sucId.value}/sixpacks/${editingSixpackId.value}`
        : `/api/admin/sucursales/${sucId.value}/sixpacks`;
    try {
        await axios.post(url, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
        await loadSixpacks();
        showSixpackModal.value = false;
    } catch (e) {
        if (e.response?.status === 422) sixpackErrors.value = e.response.data.errors ?? {};
    } finally {
        savingSixpack.value = false;
    }
}

async function removeSixpack(sp) {
    if (!confirm(`¿Eliminar sixpack "${sp.nombre}"?`)) return;
    await axios.delete(`/api/admin/sucursales/${sucId.value}/sixpacks/${sp.id}`);
    sixpacks.value = sixpacks.value.filter(x => x.id !== sp.id);
}

function stockPct(p) {
    const min = p.stock_minimo ?? 0;
    const max = p.stock_maximo;
    if (!max || max <= min) return p.stock_actual > 0 ? 100 : 0;
    return Math.min(100, Math.max(0, ((p.stock_actual - min) / (max - min)) * 100));
}

function stockColor(p) {
    if (p.stock_actual <= 0)                          return 'text-red-600';
    if (p.stock_actual < (p.stock_minimo ?? 0))       return 'text-red-600';
    if (p.stock_actual === (p.stock_minimo ?? 0))     return 'text-orange-500';
    return 'text-emerald-600';
}

function barColor(p) {
    if (p.stock_actual <= 0)                          return 'bg-red-500';
    if (p.stock_actual < (p.stock_minimo ?? 0))       return 'bg-red-500';
    if (p.stock_actual <= (p.stock_minimo ?? 0) * 1.5) return 'bg-orange-400';
    return 'bg-emerald-500';
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Productos</h2>
        <p class="text-gray-400 text-xs mt-0.5">{{ filtered.length }} de {{ productos.length }} producto(s)</p>
      </div>
      <div class="flex flex-wrap gap-1.5 self-start sm:self-auto">
        <DropdownMenu label="Exportar">
          <a :href="exportUrl('excel', 'existing')" class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 9l4.5 4.5L16.5 9M12 3v10.5"/>
            </svg>
            Excel existentes
          </a>
          <a :href="exportUrl('pdf', 'existing')" class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15A2.25 2.25 0 0 0 6.75 21h10.5a2.25 2.25 0 0 0 2.25-2.25"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 2.25 19.5 8.25"/>
            </svg>
            PDF existentes
          </a>
          <div class="border-t border-gray-100 my-0.5"/>
          <a :href="exportUrl('excel', 'all')" class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 9l4.5 4.5L16.5 9M12 3v10.5"/>
            </svg>
            Excel todo
          </a>
          <a :href="exportUrl('pdf', 'all')" class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15A2.25 2.25 0 0 0 6.75 21h10.5a2.25 2.25 0 0 0 2.25-2.25"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 2.25 19.5 8.25"/>
            </svg>
            PDF todo
          </a>
        </DropdownMenu>
        <button @click="openCreateSixpack"
          class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-colors border"
          style="background:#f5f3ff;color:#6d28d9;border-color:#c4b5fd;">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3"/>
          </svg>
          Crear Sixpack
        </button>
        <button @click="openCreate"
          class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Nuevo Producto
        </button>
      </div>
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
        <table class="w-full text-xs min-w-[820px]">
          <thead class="bg-gray-50 text-gray-400 uppercase border-b border-gray-100">
            <tr>
              <th class="px-3 py-2 text-left font-semibold tracking-wide w-24">Acciones</th>
              <th class="px-3 py-2 text-left w-8"></th>
              <th class="px-3 py-2 text-left font-semibold tracking-wide">Producto</th>
              <th class="px-3 py-2 text-left font-semibold tracking-wide hidden md:table-cell">Categoría</th>
              <th class="px-3 py-2 text-center font-semibold tracking-wide w-36">Stock</th>
              <th class="px-3 py-2 text-right font-semibold tracking-wide">P. Venta</th>
              <th class="px-3 py-2 text-right font-semibold tracking-wide hidden lg:table-cell">P. Costo</th>
              <th class="px-3 py-2 text-center font-semibold tracking-wide hidden sm:table-cell">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filtered.length === 0">
              <td colspan="8" class="px-4 py-10 text-center text-gray-400">No hay productos.</td>
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
                  <button @click="verHistorial(p)"
                    class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Historial
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
              <td class="px-3 py-1.5">
                <div class="flex flex-col items-center gap-1">
                  <!-- Actual + alerta -->
                  <div class="flex items-center gap-1">
                    <svg v-if="p.stock_actual < (p.stock_minimo ?? 0)" class="w-3 h-3 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                    <span :class="['text-base font-bold leading-none', stockColor(p)]">
                      {{ p.stock_actual }}
                    </span>
                  </div>
                  <!-- Barra de progreso -->
                  <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div :class="['h-full rounded-full transition-all', barColor(p)]"
                      :style="{ width: stockPct(p) + '%' }"/>
                  </div>
                  <!-- Etiquetas min / max -->
                  <div class="flex items-center gap-2 text-gray-400" style="font-size:10px">
                    <span class="flex items-center gap-0.5">
                      <span class="text-orange-400 font-semibold">↓</span>{{ p.stock_minimo ?? 0 }}
                    </span>
                    <span class="flex items-center gap-0.5">
                      <span class="text-blue-400 font-semibold">↑</span>{{ p.stock_maximo ?? '∞' }}
                    </span>
                  </div>
                </div>
              </td>
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

    <!-- Sixpacks section -->
    <div v-if="sixpacks.length || !loading" class="bg-white rounded-xl shadow-sm overflow-hidden border mt-4" style="border-color:#ede9fe;">
      <div class="px-4 py-3 border-b flex items-center justify-between" style="background:#f5f3ff;border-color:#ede9fe;">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4" style="color:#7c3aed;" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3"/>
          </svg>
          <span class="font-bold text-sm" style="color:#5b21b6;">Sixpacks / Packs</span>
          <span class="text-xs px-2 py-0.5 rounded-full font-semibold" style="background:#ede9fe;color:#7c3aed;">{{ sixpacks.length }}</span>
        </div>
        <button @click="openCreateSixpack" class="text-xs px-2 py-1 rounded-lg font-semibold transition-colors" style="background:#ede9fe;color:#6d28d9;">
          + Nuevo pack
        </button>
      </div>
      <div v-if="!sixpacks.length" class="py-8 text-center text-gray-400 text-xs">
        Sin sixpacks registrados. <button @click="openCreateSixpack" class="underline" style="color:#7c3aed;">Crear el primero</button>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-xs min-w-[700px]">
          <thead class="border-b text-gray-400 uppercase" style="background:#faf5ff;">
            <tr>
              <th class="px-3 py-2 text-left font-semibold tracking-wide w-20">Acciones</th>
              <th class="px-3 py-2 text-left w-8"></th>
              <th class="px-3 py-2 text-left font-semibold tracking-wide">Nombre</th>
              <th class="px-3 py-2 text-left font-semibold tracking-wide">Componentes</th>
              <th class="px-3 py-2 text-right font-semibold tracking-wide">P. Venta</th>
              <th class="px-3 py-2 text-right font-semibold tracking-wide">P. Costo</th>
              <th class="px-3 py-2 text-center font-semibold tracking-wide">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sp in sixpacks" :key="sp.id" class="border-t hover:bg-purple-50/20 transition-colors" style="border-color:#f3f0ff;">
              <td class="px-3 py-2">
                <div class="flex items-center gap-1">
                  <button @click="openEditSixpack(sp)" class="p-1 rounded hover:bg-purple-100 transition-colors" style="color:#7c3aed;" title="Editar">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z"/>
                    </svg>
                  </button>
                  <button @click="removeSixpack(sp)" class="p-1 rounded hover:bg-red-50 transition-colors text-red-400" title="Eliminar">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                    </svg>
                  </button>
                </div>
              </td>
              <td class="px-2 py-2">
                <div class="w-8 h-8 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center" style="background:#f5f3ff;">
                  <img v-if="sp.imagen" :src="`/storage/${sp.imagen}`" alt="" class="w-full h-full object-cover">
                  <svg v-else class="w-4 h-4" style="color:#c4b5fd;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3"/>
                  </svg>
                </div>
              </td>
              <td class="px-3 py-2">
                <p class="font-semibold leading-tight" style="color:#5b21b6;">{{ sp.nombre }}</p>
                <p class="text-gray-400 leading-tight font-mono" style="font-size:10px;">{{ sp.codigo_barras ?? '—' }}</p>
              </td>
              <td class="px-3 py-2">
                <div class="flex flex-wrap gap-1">
                  <span v-for="c in sp.componentes" :key="c.id"
                    class="inline-block px-1.5 py-0.5 rounded text-xs font-semibold" style="background:#ede9fe;color:#5b21b6;">
                    {{ c.producto_nombre }} ×{{ c.cantidad }}
                  </span>
                </div>
              </td>
              <td class="px-3 py-2 text-right font-bold" style="color:#5b21b6;">{{ fmtBs(sp.precio_venta) }}</td>
              <td class="px-3 py-2 text-right text-gray-500">{{ fmtBs(sp.precio_compra) }}</td>
              <td class="px-3 py-2 text-center">
                <span :class="sp.activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold">
                  {{ sp.activo ? 'Activo' : 'Inactivo' }}
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

  <!-- ══════════════ MODAL HISTORIAL ══════════════ -->
  <Teleport to="body">
    <div v-if="showHistorial"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
      @click.self="showHistorial = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[88vh] flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
          <div>
            <h3 class="font-bold text-gray-800 text-sm">Historial — {{ historialData.producto?.nombre }}</h3>
            <p class="text-gray-400 text-xs">Movimientos de compras y ventas</p>
          </div>
          <button @click="showHistorial = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 px-5 pt-3 pb-0 border-b border-gray-100 flex-shrink-0">
          <button @click="historialTab = 'compras'"
            :class="['px-4 py-2 text-xs font-semibold border-b-2 -mb-px transition-colors',
              historialTab === 'compras' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600']">
            Compras
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs"
              :class="historialTab === 'compras' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500'">
              {{ historialData.compras.length }}
            </span>
          </button>
          <button @click="historialTab = 'ventas'"
            :class="['px-4 py-2 text-xs font-semibold border-b-2 -mb-px transition-colors',
              historialTab === 'ventas' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-400 hover:text-gray-600']">
            Ventas
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs"
              :class="historialTab === 'ventas' ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-500'">
              {{ historialData.ventas.length }}
            </span>
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="loadingHistorial" class="flex justify-center items-center py-16">
            <svg class="animate-spin w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
          </div>

          <!-- TAB COMPRAS -->
          <div v-else-if="historialTab === 'compras'">
            <div v-if="!historialData.compras.length" class="py-14 text-center text-gray-400 text-xs">Sin compras registradas.</div>
            <table v-else class="w-full text-xs">
              <thead class="bg-gray-50 text-gray-400 uppercase border-b border-gray-100 sticky top-0">
                <tr>
                  <th class="px-4 py-2 text-left font-semibold tracking-wide">Fecha</th>
                  <th class="px-4 py-2 text-left font-semibold tracking-wide">Compra</th>
                  <th class="px-4 py-2 text-left font-semibold tracking-wide">Proveedor</th>
                  <th class="px-4 py-2 text-center font-semibold tracking-wide">Comprado</th>
                  <th class="px-4 py-2 text-center font-semibold tracking-wide">Vendido</th>
                  <th class="px-4 py-2 text-center font-semibold tracking-wide">Disponible</th>
                  <th class="px-4 py-2 text-right font-semibold tracking-wide">P. Unitario</th>
                  <th class="px-4 py-2 text-right font-semibold tracking-wide">Total</th>
                  <th class="px-4 py-2 text-center font-semibold tracking-wide">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in historialData.compras" :key="c.id"
                  :class="['border-t border-gray-50', c.estado === 'anulada' ? 'opacity-50 bg-red-50/20' : 'hover:bg-blue-50/20']">
                  <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ fmtFecha(c.fecha) }}</td>
                  <td class="px-4 py-2.5 font-mono text-gray-400">#{{ c.compra_id }}</td>
                  <td class="px-4 py-2.5 font-semibold text-gray-700">{{ c.proveedor }}</td>
                  <td class="px-4 py-2.5 text-center font-bold text-gray-800">{{ c.cantidad }}</td>
                  <td class="px-4 py-2.5 text-center text-emerald-600 font-semibold">{{ c.cantidad_vendida }}</td>
                  <td class="px-4 py-2.5 text-center">
                    <span :class="['font-bold', c.disponible === 0 ? 'text-gray-400' : 'text-blue-600']">
                      {{ c.disponible }}
                    </span>
                  </td>
                  <td class="px-4 py-2.5 text-right text-gray-600">{{ fmtBs(c.precio_unitario) }}</td>
                  <td class="px-4 py-2.5 text-right font-semibold text-gray-800">{{ fmtBs(c.precio_total) }}</td>
                  <td class="px-4 py-2.5 text-center">
                    <span :class="c.estado === 'activa' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                      class="inline-block px-2 py-0.5 rounded-full font-semibold capitalize">
                      {{ c.estado }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TAB VENTAS -->
          <div v-else>
            <div v-if="!historialData.ventas.length" class="py-14 text-center text-gray-400 text-xs">Sin ventas registradas.</div>
            <table v-else class="w-full text-xs">
              <thead class="bg-gray-50 text-gray-400 uppercase border-b border-gray-100 sticky top-0">
                <tr>
                  <th class="px-4 py-2 text-left font-semibold tracking-wide">Fecha</th>
                  <th class="px-4 py-2 text-left font-semibold tracking-wide">Folio</th>
                  <th class="px-4 py-2 text-left font-semibold tracking-wide">Cliente</th>
                  <th class="px-4 py-2 text-left font-semibold tracking-wide">Usuario</th>
                  <th class="px-4 py-2 text-center font-semibold tracking-wide">Cantidad</th>
                  <th class="px-4 py-2 text-right font-semibold tracking-wide">P. Unitario</th>
                  <th class="px-4 py-2 text-right font-semibold tracking-wide">Subtotal</th>
                  <th class="px-4 py-2 text-center font-semibold tracking-wide">Estado</th>
                  <th class="px-4 py-2 text-center font-semibold tracking-wide">Pago</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="v in historialData.ventas" :key="v.id"
                  :class="['border-t border-gray-50', v.estado === 'cancelada' ? 'opacity-50 bg-red-50/20' : 'hover:bg-emerald-50/20']">
                  <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">{{ fmtFecha(v.fecha) }}</td>
                  <td class="px-4 py-2.5 font-mono text-gray-400">{{ v.folio }}</td>
                  <td class="px-4 py-2.5 font-semibold text-gray-700">{{ v.cliente }}</td>
                  <td class="px-4 py-2.5 text-gray-500">{{ v.usuario }}</td>
                  <td class="px-4 py-2.5 text-center font-bold text-gray-800">{{ v.cantidad }}</td>
                  <td class="px-4 py-2.5 text-right text-gray-600">{{ fmtBs(v.precio_unitario) }}</td>
                  <td class="px-4 py-2.5 text-right font-semibold text-gray-800">{{ fmtBs(v.subtotal) }}</td>
                  <td class="px-4 py-2.5 text-center">
                    <span :class="v.estado === 'completada' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'"
                      class="inline-block px-2 py-0.5 rounded-full font-semibold capitalize">
                      {{ v.estado }}
                    </span>
                  </td>
                  <td class="px-4 py-2.5 text-center">
                    <span class="bg-gray-100 text-gray-600 inline-block px-2 py-0.5 rounded-full font-semibold">
                      {{ pagoLabel[v.metodo_pago] ?? v.metodo_pago }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </Teleport>

  <!-- ══════════════ MODAL SIXPACK ══════════════ -->
  <Teleport to="body">
    <div v-if="showSixpackModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="showSixpackModal = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto">

        <div class="px-5 py-3.5 border-b flex items-center justify-between sticky top-0 bg-white rounded-t-2xl" style="border-color:#ede9fe;">
          <h3 class="font-bold text-sm" style="color:#5b21b6;">
            {{ editingSixpackId ? 'Editar Sixpack' : 'Nuevo Sixpack' }}
            <span class="font-normal text-gray-400 text-xs ml-1">— {{ sucursal?.nombre }}</span>
          </h3>
          <button @click="showSixpackModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveSixpack" class="px-5 py-4 space-y-3">

          <!-- Imagen -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Imagen del pack</label>
            <input id="sp-img-input" type="file" accept="image/*" @change="onSixpackImg" class="hidden">
            <div @click="document.getElementById('sp-img-input').click()"
              @dragover.prevent="sixpackDragOver = true" @dragleave.prevent="sixpackDragOver = false" @drop.prevent="onSixpackDrop"
              class="flex flex-col items-center justify-center w-full rounded-xl border-2 border-dashed cursor-pointer transition-colors relative overflow-hidden select-none"
              :class="sixpackImgPreview ? 'h-32 border-purple-300 bg-purple-50' : (sixpackDragOver ? 'h-20 border-purple-400 bg-purple-50' : 'h-20 border-gray-200 bg-gray-50 hover:border-purple-300')">
              <img v-if="sixpackImgPreview" :src="sixpackImgPreview" alt="" class="absolute inset-0 w-full h-full object-contain p-1 pointer-events-none">
              <template v-if="!sixpackImgPreview">
                <svg class="w-6 h-6 text-gray-300 mb-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                </svg>
                <p class="text-xs text-gray-400"><span class="font-semibold" style="color:#7c3aed;">Seleccionar</span> o arrastrar imagen</p>
              </template>
            </div>
          </div>

          <!-- Código + Nombre -->
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Código de barras</label>
              <input v-model="sixpackForm.codigo_barras" type="text" placeholder="Opcional"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre *</label>
              <input v-model="sixpackForm.nombre" type="text" required placeholder="Ej: Sixpack Cerveza Paceña"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500">
              <p v-if="sixpackErrors.nombre" class="text-red-500 text-xs mt-0.5">{{ sixpackErrors.nombre[0] }}</p>
            </div>
          </div>

          <!-- Categoría -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Categoría</label>
            <select v-model="sixpackForm.categoria_id"
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
              <option value="">Sin categoría</option>
              <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
          </div>

          <!-- Precios -->
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Precio Costo *</label>
              <input v-model="sixpackForm.precio_compra" type="number" step="0.01" min="0" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500">
              <p v-if="sixpackErrors.precio_compra" class="text-red-500 text-xs mt-0.5">{{ sixpackErrors.precio_compra[0] }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Precio Venta *</label>
              <input v-model="sixpackForm.precio_venta" type="number" step="0.01" min="0.01" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500">
              <p v-if="sixpackErrors.precio_venta" class="text-red-500 text-xs mt-0.5">{{ sixpackErrors.precio_venta[0] }}</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Precio Mayoreo</label>
              <input v-model="sixpackForm.precio_mayoreo" type="number" step="0.01" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
          </div>

          <!-- Stock min/max + activo -->
          <div class="grid grid-cols-3 gap-3 items-end">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Stock Mínimo</label>
              <input v-model="sixpackForm.stock_minimo" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Stock Máximo</label>
              <input v-model="sixpackForm.stock_maximo" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="flex items-center gap-2 pb-1.5">
              <input id="sp-activo-chk" v-model="sixpackForm.activo" type="checkbox" class="w-4 h-4 rounded accent-purple-600">
              <label for="sp-activo-chk" class="text-xs font-semibold text-gray-600">Activo</label>
            </div>
          </div>

          <!-- Componentes -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-bold text-gray-700">Productos componentes *</label>
              <button type="button" @click="addComponente"
                class="text-xs px-2 py-1 rounded-lg font-semibold transition-colors" style="background:#ede9fe;color:#6d28d9;">
                + Agregar
              </button>
            </div>
            <div class="space-y-2">
              <div v-for="(comp, i) in sixpackForm.componentes" :key="i" class="flex gap-2 items-center">
                <select v-model="comp.producto_id" required
                  class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
                  <option value="">Seleccionar producto...</option>
                  <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                </select>
                <div class="flex items-center gap-1 flex-shrink-0" style="min-width:110px;">
                  <span class="text-xs text-gray-500">×</span>
                  <input v-model.number="comp.cantidad" type="number" min="1" required
                    class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-center focus:outline-none focus:ring-2 focus:ring-purple-500" style="width:64px;">
                </div>
                <button type="button" @click="removeComponente(i)"
                  class="text-red-400 hover:text-red-600 text-lg font-bold leading-none flex-shrink-0 px-1"
                  :class="sixpackForm.componentes.length <= 1 ? 'opacity-30 cursor-not-allowed' : ''">&times;</button>
              </div>
            </div>
            <p class="text-xs text-gray-400 mt-1">Al vender este pack se descontará el stock de cada componente.</p>
            <p v-if="sixpackErrors.componentes" class="text-red-500 text-xs mt-0.5">{{ sixpackErrors.componentes[0] }}</p>
          </div>

          <div class="flex gap-2 pt-1">
            <button type="submit" :disabled="savingSixpack"
              class="flex-1 text-white py-2 rounded-lg text-xs font-semibold transition-colors disabled:opacity-60"
              style="background:#7c3aed;">
              {{ savingSixpack ? 'Guardando...' : (editingSixpackId ? 'Guardar cambios' : 'Crear sixpack') }}
            </button>
            <button type="button" @click="showSixpackModal = false"
              class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg text-xs font-semibold transition-colors">
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
