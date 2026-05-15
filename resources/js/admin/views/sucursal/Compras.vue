<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth.js';
import DropdownMenu from '../../components/DropdownMenu.vue';

const route    = useRoute();
const router   = useRouter();
const auth     = useAuthStore();
const sucId    = computed(() => route.params.sucursalId);
const sucursal = computed(() => auth.sucursales.find(s => s.id == sucId.value));

// ── Tabs ──────────────────────────────────────────────────────
const tab = ref('nueva'); // 'nueva' | 'historial'

function tabFromRoute() {
    return route.name === 'sucursal.compras.historial' ? 'historial' : 'nueva';
}

function goTab(nextTab) {
    tab.value = nextTab;
    return router.push({
        name: nextTab === 'historial' ? 'sucursal.compras.historial' : 'sucursal.compras.nueva',
        params: { sucursalId: sucId.value },
    });
}

// ── Proveedores ───────────────────────────────────────────────
const proveedores     = ref([]);
const proveedorId     = ref(null);
const showNuevoProv   = ref(false);
const savingProv      = ref(false);
const nuevoProv       = ref({ nombre: '', telefono: '', email: '' });
const erroresProv     = ref({});

async function loadProveedores() {
    const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/proveedores`);
    proveedores.value = data;
    // Auto-select "Proveedor General" or the first one
    if (!proveedorId.value && data.length) {
        const general = data.find(p => p.nombre === 'Proveedor General');
        proveedorId.value = general ? general.id : data[0].id;
    }
}

async function guardarNuevoProveedor() {
    erroresProv.value = {};
    if (!nuevoProv.value.nombre.trim()) {
        erroresProv.value.nombre = 'El nombre es requerido.';
        return;
    }
    savingProv.value = true;
    try {
        const { data } = await axios.post(`/api/admin/sucursales/${sucId.value}/proveedores`, nuevoProv.value);
        proveedores.value.push(data);
        proveedorId.value = data.id;
        showNuevoProv.value = false;
        nuevoProv.value = { nombre: '', telefono: '', email: '' };
    } catch (e) {
        if (e.response?.status === 422)
            erroresProv.value = e.response.data.errors ?? {};
        else
            alert(e.response?.data?.message ?? 'Error al guardar proveedor.');
    } finally {
        savingProv.value = false;
    }
}

// ── Historial ─────────────────────────────────────────────────
const compras   = ref([]);
const stats     = ref({ total_activas: 0, total_anuladas: 0, count: 0 });
const loadingH  = ref(false);
const desde     = ref(today());
const hasta     = ref(today());
const compraDetalle = ref(null);

function today() {
    return new Date().toISOString().slice(0, 10);
}

async function loadHistorial() {
    loadingH.value = true;
    try {
        const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/compras`, {
            params: { desde: desde.value, hasta: hasta.value },
        });
        compras.value = data.compras;
        stats.value   = data.stats;
    } finally {
        loadingH.value = false;
    }
}

async function anular(c) {
    const nombre = c.proveedor?.nombre ?? '—';
    if (!confirm(`¿Anular compra #${c.id} de "${nombre}"? Esto revertirá el stock.`)) return;
    try {
        await axios.patch(`/api/admin/sucursales/${sucId.value}/compras/${c.id}/anular`);
        await loadHistorial();
    } catch (e) {
        alert(e.response?.data?.message ?? 'Error al anular.');
    }
}

function verCompra(c) {
    compraDetalle.value = c;
}

// ── Nueva Compra ──────────────────────────────────────────────
const productos    = ref([]);
const searchProd   = ref('');
const cart         = ref([]);
const comentarios  = ref('');
const metodoPago   = ref('efectivo');
const saving       = ref(false);
const errores      = ref({});

const prodFiltrados = computed(() => {
    const s = searchProd.value.toLowerCase();
    return s
        ? productos.value.filter(p =>
            p.nombre.toLowerCase().includes(s) ||
            (p.codigo_barras ?? '').includes(s))
        : productos.value;
});

const total = computed(() =>
    cart.value.reduce((sum, i) => sum + precioTotalNumero(i), 0)
);

async function loadProductos() {
    const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/productos`);
    productos.value = data.productos.filter(p => p.activo);
}

function agregarProducto(p) {
    const existing = cart.value.find(i => i.producto_id === p.id);
    if (existing) {
        existing.cantidad++;
        actualizarTotalDesdeUnitario(existing);
    } else {
        const precioCompra = parseFloat(p.precio_compra) || 0;
        cart.value.push({
            producto_id:     p.id,
            nombre:          p.nombre,
            codigo_barras:   p.codigo_barras,
            stock_actual:    p.stock_actual,
            cantidad:        1,
            precio_unitario: precioCompra,
            total_linea:     precioCompra,
        });
    }
}

function quitarItem(idx) {
    cart.value.splice(idx, 1);
}

function precioTotalItem(item) {
    return precioTotalNumero(item).toFixed(2);
}

function precioTotalNumero(item) {
    const totalLinea = parseNumber(item.total_linea);
    if (Number.isFinite(totalLinea)) {
        return totalLinea;
    }

    const cantidad = parseNumber(item.cantidad);
    const precioUnitario = parseNumber(item.precio_unitario);

    return Number.isFinite(cantidad) && Number.isFinite(precioUnitario)
        ? cantidad * precioUnitario
        : 0;
}

function parseNumber(value) {
    if (value === null || value === undefined || value === '') {
        return NaN;
    }

    return parseFloat(String(value).replace(',', '.'));
}

function round2(value) {
    return Number(value.toFixed(2));
}

function actualizarTotalDesdeUnitario(item) {
    const cantidad = parseFloat(item.cantidad);
    const precioUnitario = parseNumber(item.precio_unitario);

    if (!Number.isFinite(cantidad) || cantidad <= 0 || !Number.isFinite(precioUnitario)) {
        return;
    }

    item.total_linea = round2(cantidad * precioUnitario);
}

function actualizarCantidadItem(item) {
    if (!Number.isFinite(parseNumber(item.cantidad)) || parseNumber(item.cantidad) <= 0) {
        item.cantidad = 1;
    }

    actualizarTotalDesdeUnitario(item);
}

function actualizarPrecioUnitarioItem(item) {
    actualizarTotalDesdeUnitario(item);
}

function actualizarTotalItem(item, value) {
    const totalItem = parseNumber(value);
    const cantidad = parseNumber(item.cantidad);

    if (!Number.isFinite(totalItem) || !Number.isFinite(cantidad) || cantidad <= 0) {
        return;
    }

    item.total_linea = totalItem;
    item.precio_unitario = round2(totalItem / cantidad);
}

async function registrarCompra() {
    errores.value = {};
    if (!proveedorId.value)   { errores.value.proveedor = 'Selecciona un proveedor.'; return; }
    if (!cart.value.length)   { errores.value.items = 'Agrega al menos un producto.'; return; }

    saving.value = true;
    try {
        await axios.post(`/api/admin/sucursales/${sucId.value}/compras`, {
            proveedor_id: proveedorId.value,
            comentarios:  comentarios.value || null,
            metodo_pago:  metodoPago.value,
            items: cart.value.map(i => ({
                producto_id:     i.producto_id,
                cantidad:        i.cantidad,
                precio_unitario: i.precio_unitario,
                precio_total:    precioTotalNumero(i),
            })),
        });
        cart.value        = [];
        comentarios.value = '';
        metodoPago.value  = 'efectivo';
        await loadProductos();
        await goTab('historial');
        await loadHistorial();
    } catch (e) {
        if (e.response?.status === 422)
            errores.value = e.response.data.errors ?? { general: [e.response.data.message] };
        else
            alert(e.response?.data?.message ?? 'Error al registrar compra.');
    } finally {
        saving.value = false;
    }
}

// ── Watch route / tab ─────────────────────────────────────────
watch(sucId, async () => {
    cart.value = [];
    proveedorId.value = null;
    tab.value = tabFromRoute();
    await Promise.all([loadProductos(), loadProveedores(), loadHistorial()]);
}, { immediate: true });

watch(() => route.name, () => {
    tab.value = tabFromRoute();
});

watch(tab, (t) => { if (t === 'historial') loadHistorial(); });

// ── Format helpers ────────────────────────────────────────────
const fmtBs    = v => `Bs ${parseFloat(v).toFixed(2)}`;
const fmtFecha = d => d ? new Date(d).toLocaleString('es-BO') : '—';
const userName  = u => u?.nickname || u?.name || '—';

const pagoColor = { efectivo: 'bg-green-100 text-green-700', tarjeta: 'bg-blue-100 text-blue-700', transferencia: 'bg-purple-100 text-purple-700' };
const pagoLabel = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', transferencia: 'Transferencia' };
</script>

<template>
  <div class="flex flex-col h-full">

    <!-- Header + tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Compras</h2>
        <p class="text-gray-400 text-xs mt-0.5">{{ sucursal?.nombre }}</p>
      </div>
      <div class="flex gap-1 bg-gray-100 p-1 rounded-xl">
        <button @click="goTab('nueva')"
          :class="['px-4 py-1.5 rounded-lg text-xs font-semibold transition-colors',
            tab === 'nueva' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          Nueva Compra
        </button>
        <button @click="goTab('historial')"
          :class="['px-4 py-1.5 rounded-lg text-xs font-semibold transition-colors',
            tab === 'historial' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          Historial
        </button>
      </div>
    </div>

    <!-- ══════════════ TAB: NUEVA COMPRA ══════════════ -->
    <div v-if="tab === 'nueva'" class="flex flex-col xl:flex-row gap-4 flex-1 min-h-0">

      <!-- Panel izquierdo: productos -->
      <div class="w-full xl:w-[58%] 2xl:w-[62%] flex-shrink-0 flex flex-col bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden min-h-[360px]">
        <div class="p-3 border-b border-gray-100">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input v-model="searchProd" type="text" placeholder="Buscar producto..."
              class="w-full border border-gray-200 rounded-lg pl-10 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
        <div class="flex-1 overflow-y-auto p-3">
          <div v-if="!prodFiltrados.length" class="py-8 text-center text-gray-400 text-xs">Sin productos</div>
          <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 gap-2.5">
            <button v-for="p in prodFiltrados" :key="p.id"
              @click="agregarProducto(p)"
              class="group relative h-32 overflow-hidden rounded-lg border border-gray-200 bg-gray-100 text-left shadow-sm transition hover:border-blue-400 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <img v-if="p.imagen" :src="`/storage/${p.imagen}`" class="absolute inset-0 w-full h-full object-cover transition-transform duration-200 group-hover:scale-105">
            <div v-else class="absolute inset-0 flex items-center justify-center bg-gray-100">
              <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
              </svg>
            </div>
            <div class="absolute inset-x-0 bottom-0 bg-black/60 px-2 py-1.5 text-white">
              <p class="text-[12px] font-bold leading-tight overflow-hidden" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ p.nombre }}</p>
              <div class="mt-1 flex items-center justify-between gap-2 text-[11px] font-semibold leading-none text-white/90">
                <span>Stock: {{ p.stock_actual }}</span>
                <span class="rounded bg-orange-500 px-1.5 py-1 text-black">{{ fmtBs(p.precio_compra) }}</span>
              </div>
            </div>
            <div class="absolute right-1.5 top-1.5 rounded-full bg-blue-600/90 p-1 text-white opacity-0 shadow transition group-hover:opacity-100">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
              </svg>
            </div>
            </button>
          </div>
        </div>
      </div>

      <!-- Panel derecho: datos + carrito -->
      <div class="flex-1 flex flex-col gap-3 min-w-0">

        <!-- Datos de la compra -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 space-y-3">

          <!-- Selector de proveedor -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Proveedor *</label>
            <div class="flex gap-2">
              <select v-model="proveedorId"
                :class="['flex-1 border rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white',
                  errores.proveedor ? 'border-red-400' : 'border-gray-200']">
                <option :value="null" disabled>Seleccionar proveedor...</option>
                <option v-for="prov in proveedores" :key="prov.id" :value="prov.id">
                  {{ prov.nombre }}
                </option>
              </select>
              <button @click="showNuevoProv = true"
                class="flex items-center gap-1 px-3 py-1.5 border border-blue-200 text-blue-600 hover:bg-blue-50 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Nuevo
              </button>
            </div>
            <p v-if="errores.proveedor" class="text-red-500 text-xs mt-1">{{ errores.proveedor }}</p>
          </div>

          <!-- Método de pago + Comentarios + Botón -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Método de pago</label>
              <select v-model="metodoPago"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="transferencia">Transferencia</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Comentarios</label>
              <input v-model="comentarios" type="text" placeholder="Opcional"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
          </div>

          <button @click="registrarCompra" :disabled="saving || !cart.length"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-2 rounded-xl text-sm font-bold transition-colors flex items-center justify-center gap-2">
            <svg v-if="!saving" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            {{ saving ? 'Registrando...' : `Registrar Compra · ${fmtBs(total)}` }}
          </button>
        </div>

        <!-- Error general -->
        <div v-if="errores.general || errores.items"
          class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-3 py-2">
          {{ errores.general?.[0] ?? errores.items }}
        </div>

        <!-- Tabla carrito -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex-1">
          <div class="overflow-auto h-full">
            <table class="w-full text-xs">
              <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
                <tr>
                  <th class="px-3 py-2 text-left font-semibold text-gray-400 uppercase tracking-wide">Producto</th>
                  <th class="px-3 py-2 text-center font-semibold text-gray-400 uppercase tracking-wide w-24">Cantidad</th>
                  <th class="px-3 py-2 text-right font-semibold text-gray-400 uppercase tracking-wide w-28">P. Unitario</th>
                  <th class="px-3 py-2 text-right font-semibold text-gray-400 uppercase tracking-wide w-24">Total</th>
                  <th class="px-3 py-2 w-8"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!cart.length">
                  <td colspan="5" class="py-12 text-center text-gray-400">
                    <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>
                    </svg>
                    Selecciona productos del panel izquierdo
                  </td>
                </tr>
                <tr v-for="(item, idx) in cart" :key="item.producto_id"
                  class="border-t border-gray-50 hover:bg-gray-50/50">
                  <td class="px-3 py-2">
                    <p class="font-semibold text-gray-800">{{ item.nombre }}</p>
                    <p class="text-gray-400" style="font-size:10px">{{ item.codigo_barras ?? '—' }} · Stock: {{ item.stock_actual }}</p>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model.number="item.cantidad" type="number" min="1"
                      @input="actualizarCantidadItem(item)"
                      class="w-full text-center border border-gray-200 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                  </td>
                  <td class="px-3 py-2">
                    <div class="flex items-center justify-end gap-1">
                      <span class="text-gray-400">Bs</span>
                      <input v-model.number="item.precio_unitario" type="number" min="0" step="0.01"
                        @input="actualizarPrecioUnitarioItem(item)"
                        class="w-20 text-right border border-gray-200 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                  </td>
                  <td class="px-3 py-2">
                    <div class="flex items-center justify-end gap-1">
                      <span class="text-gray-400">Bs</span>
                      <input v-model.number="item.total_linea" type="number" min="0" step="0.01"
                        @input="actualizarTotalItem(item, $event.target.value)"
                        @blur="item.total_linea = round2(precioTotalNumero(item))"
                        class="w-20 text-right border border-gray-200 rounded-lg px-2 py-1 text-xs font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                  </td>
                  <td class="px-2 py-2">
                    <button @click="quitarItem(idx)"
                      class="p-1 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot v-if="cart.length" class="border-t-2 border-gray-200 bg-gray-50">
                <tr>
                  <td colspan="3" class="px-3 py-2 text-right text-xs font-bold text-gray-600">TOTAL</td>
                  <td class="px-3 py-2 text-right font-bold text-gray-900">{{ fmtBs(total) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>
    </div>

    <!-- ══════════════ TAB: HISTORIAL ══════════════ -->
    <div v-if="tab === 'historial'" class="flex flex-col gap-4">

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-3">
        <div class="bg-green-600 text-white rounded-xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-white/70">Compras Activas</p>
            <p class="text-xl font-bold leading-tight">{{ fmtBs(stats.total_activas) }}</p>
          </div>
        </div>
        <div class="bg-red-600 text-white rounded-xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-white/70">Compras Anuladas</p>
            <p class="text-xl font-bold leading-tight">{{ fmtBs(stats.total_anuladas) }}</p>
          </div>
        </div>
        <div class="bg-blue-600 text-white rounded-xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-white/70">Total Compras</p>
            <p class="text-xl font-bold leading-tight">{{ stats.count }}</p>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="flex flex-wrap gap-2 items-end">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Desde</label>
          <input v-model="desde" type="date"
            class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Hasta</label>
          <input v-model="hasta" type="date"
            class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
        </div>
        <button @click="loadHistorial"
          class="flex items-center gap-1.5 bg-slate-700 hover:bg-slate-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
          </svg>
          Buscar
        </button>
      </div>

      <!-- Tabla historial -->
      <div v-if="loadingH" class="flex justify-center py-12">
        <svg class="animate-spin w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
      </div>
      <div v-else class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-xs min-w-[800px]">
            <thead class="bg-gray-50 border-b border-gray-100 text-gray-400 uppercase">
              <tr>
                <th class="px-3 py-2 text-left font-semibold tracking-wide w-24">Acciones</th>
                <th class="px-3 py-2 text-left font-semibold tracking-wide w-20">ID</th>
                <th class="px-3 py-2 text-left font-semibold tracking-wide">Fecha</th>
                <th class="px-3 py-2 text-left font-semibold tracking-wide">Proveedor</th>
                <th class="px-3 py-2 text-left font-semibold tracking-wide">Usuario</th>
                <th class="px-3 py-2 text-center font-semibold tracking-wide w-20">Estado</th>
                <th class="px-3 py-2 text-right font-semibold tracking-wide w-24">Total</th>
                <th class="px-3 py-2 text-left font-semibold tracking-wide">Detalle</th>
                <th class="px-3 py-2 text-center font-semibold tracking-wide w-24">Pago</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!compras.length">
                <td colspan="9" class="py-10 text-center text-gray-400">No hay compras en este período.</td>
              </tr>
              <tr v-for="c in compras" :key="c.id"
                :class="['border-t border-gray-50 transition-colors',
                  c.estado === 'anulada' ? 'opacity-50 bg-red-50/30' : 'hover:bg-blue-50/20']">
                <td class="px-3 py-2">
                  <DropdownMenu>
                    <button @click="verCompra(c)"
                      class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                      <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                      </svg>
                      Ver compra
                    </button>
                    <div v-if="c.estado === 'activa'" class="border-t border-gray-100 my-0.5"/>
                    <button v-if="c.estado === 'activa'" @click="anular(c)"
                      class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
                      </svg>
                      Anular
                    </button>
                  </DropdownMenu>
                </td>
                <td class="px-3 py-2 font-mono text-gray-500">#{{ c.id }}</td>
                <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ fmtFecha(c.created_at) }}</td>
                <td class="px-3 py-2 font-semibold text-gray-800">{{ c.proveedor?.nombre ?? '—' }}</td>
                <td class="px-3 py-2 text-gray-500">{{ userName(c.user) }}</td>
                <td class="px-3 py-2 text-center">
                  <span :class="c.estado === 'activa' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                    class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold capitalize">
                    {{ c.estado }}
                  </span>
                </td>
                <td class="px-3 py-2 text-right font-bold text-gray-800">{{ fmtBs(c.total) }}</td>
                <td class="px-3 py-2 text-gray-500 max-w-xs">
                  <p class="truncate">
                    {{ c.detalles?.map(d => `${d.cantidad}x ${d.producto?.nombre}`).join(', ') }}
                  </p>
                  <p v-if="c.comentarios" class="text-gray-400 italic truncate">{{ c.comentarios }}</p>
                </td>
                <td class="px-3 py-2 text-center">
                  <span :class="pagoColor[c.metodo_pago] ?? 'bg-gray-100 text-gray-600'"
                    class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold">
                    {{ pagoLabel[c.metodo_pago] ?? c.metodo_pago }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>

  <!-- ══════════════ MODAL: NUEVO PROVEEDOR ══════════════ -->
  <Teleport to="body">
    <div v-if="compraDetalle"
      class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="compraDetalle = null">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
          <div>
            <h3 class="text-base font-bold text-gray-800">Compra #{{ compraDetalle.id }}</h3>
            <p class="text-xs text-gray-400">{{ fmtFecha(compraDetalle.created_at) }}</p>
          </div>
          <button @click="compraDetalle = null"
            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="p-5 space-y-4">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
              <p class="text-xs text-gray-400">Proveedor</p>
              <p class="text-sm font-semibold text-gray-800">{{ compraDetalle.proveedor?.nombre ?? 'Sin proveedor' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Usuario</p>
              <p class="text-sm font-semibold text-gray-800">{{ userName(compraDetalle.user) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Pago</p>
              <p class="text-sm font-semibold text-gray-800">{{ pagoLabel[compraDetalle.metodo_pago] ?? compraDetalle.metodo_pago }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Total</p>
              <p class="text-sm font-bold text-gray-900">{{ fmtBs(compraDetalle.total) }}</p>
            </div>
          </div>

          <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-xs">
              <thead class="bg-gray-50 text-gray-400 uppercase">
                <tr>
                  <th class="px-3 py-2 text-left font-semibold">Producto</th>
                  <th class="px-3 py-2 text-center font-semibold w-20">Cant.</th>
                  <th class="px-3 py-2 text-right font-semibold w-28">Unitario</th>
                  <th class="px-3 py-2 text-right font-semibold w-28">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in compraDetalle.detalles" :key="d.id" class="border-t border-gray-50">
                  <td class="px-3 py-2 font-semibold text-gray-700">{{ d.producto?.nombre ?? 'Producto eliminado' }}</td>
                  <td class="px-3 py-2 text-center text-gray-600">{{ d.cantidad }}</td>
                  <td class="px-3 py-2 text-right text-gray-600">{{ fmtBs(d.precio_unitario) }}</td>
                  <td class="px-3 py-2 text-right font-bold text-gray-800">{{ fmtBs(d.precio_total ?? (d.cantidad * d.precio_unitario)) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="compraDetalle.comentarios" class="rounded-xl bg-gray-50 px-3 py-2">
            <p class="text-xs text-gray-400">Comentarios</p>
            <p class="text-sm text-gray-700">{{ compraDetalle.comentarios }}</p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <Teleport to="body">
    <div v-if="showNuevoProv"
      class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="showNuevoProv = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-bold text-gray-800">Nuevo Proveedor</h3>
          <button @click="showNuevoProv = false"
            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre *</label>
            <input v-model="nuevoProv.nombre" type="text" placeholder="Nombre del proveedor"
              :class="['w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
                erroresProv.nombre ? 'border-red-400' : 'border-gray-200']"
              @keyup.enter="guardarNuevoProveedor">
            <p v-if="erroresProv.nombre" class="text-red-500 text-xs mt-0.5">
              {{ Array.isArray(erroresProv.nombre) ? erroresProv.nombre[0] : erroresProv.nombre }}
            </p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Teléfono</label>
            <input v-model="nuevoProv.telefono" type="text" placeholder="Ej: 71234567"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
            <input v-model="nuevoProv.email" type="email" placeholder="correo@ejemplo.com"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
        </div>

        <div class="flex gap-2 mt-5">
          <button @click="showNuevoProv = false"
            class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors">
            Cancelar
          </button>
          <button @click="guardarNuevoProveedor" :disabled="savingProv"
            class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-2 rounded-xl text-sm font-bold transition-colors">
            {{ savingProv ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
