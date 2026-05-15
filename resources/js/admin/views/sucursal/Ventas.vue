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
const tab = ref('historial');

function tabFromRoute() {
    return route.name === 'sucursal.ventas.nueva' ? 'nueva' : 'historial';
}

function goTab(nextTab) {
    tab.value = nextTab;
    return router.push({
        name: nextTab === 'nueva' ? 'sucursal.ventas.nueva' : 'sucursal.ventas.historial',
        params: { sucursalId: sucId.value },
    });
}

// ── Clientes ──────────────────────────────────────────────────
const clientes      = ref([]);
const clienteId     = ref(null);
const showNuevoCli  = ref(false);
const savingCli     = ref(false);
const nuevoCli      = ref({ nombre: '', telefono: '', email: '' });
const erroresCli    = ref({});

async function loadClientes() {
    const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/clientes`);
    clientes.value = data;
}

async function guardarNuevoCliente() {
    erroresCli.value = {};
    if (!nuevoCli.value.nombre.trim()) {
        erroresCli.value.nombre = 'El nombre es requerido.';
        return;
    }
    savingCli.value = true;
    try {
        const { data } = await axios.post(`/api/admin/sucursales/${sucId.value}/clientes`, nuevoCli.value);
        clientes.value.push(data);
        clienteId.value = data.id;
        showNuevoCli.value = false;
        nuevoCli.value = { nombre: '', telefono: '', email: '' };
    } catch (e) {
        if (e.response?.status === 422)
            erroresCli.value = e.response.data.errors ?? {};
        else
            alert(e.response?.data?.message ?? 'Error al guardar cliente.');
    } finally {
        savingCli.value = false;
    }
}

// ── Historial ─────────────────────────────────────────────────
const ventas       = ref([]);
const stats        = ref({ total_completadas: 0, total_canceladas: 0, count: 0, ganancia: 0 });
const loadingH     = ref(false);
const desde        = ref(today());
const hasta        = ref(today());
const ventaDetalle = ref(null);

function today() {
    return new Date().toISOString().slice(0, 10);
}

async function loadHistorial() {
    loadingH.value = true;
    try {
        const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/ventas`, {
            params: { desde: desde.value, hasta: hasta.value },
        });
        ventas.value = data.ventas;
        stats.value  = data.stats;
    } finally {
        loadingH.value = false;
    }
}

async function cancelar(v) {
    const cliente = v.cliente?.nombre ?? 'cliente general';
    if (!confirm(`¿Cancelar venta ${v.folio} de "${cliente}"? Esto restaurará el stock.`)) return;
    try {
        await axios.patch(`/api/admin/sucursales/${sucId.value}/ventas/${v.id}/cancelar`);
        await loadHistorial();
    } catch (e) {
        alert(e.response?.data?.message ?? 'Error al cancelar.');
    }
}

function verVenta(v) {
    ventaDetalle.value = v;
}

// ── Nueva Venta ───────────────────────────────────────────────
const productos    = ref([]);
const searchProd   = ref('');
const cart         = ref([]);
const metodoPago   = ref('efectivo');
const comentarios  = ref('');
const saving       = ref(false);
const errores      = ref({});

const prodFiltrados = computed(() => {
    const s = searchProd.value.toLowerCase();
    return s
        ? productos.value.filter(p =>
            p.nombre.toLowerCase().includes(s) ||
            (p.codigo_barras ?? '').toLowerCase().includes(s))
        : productos.value;
});

function onSearchEnter() {
    if (!searchProd.value.trim()) return;

    // Coincidencia exacta por código de barras (prioridad)
    const exacto = productos.value.find(
        p => p.codigo_barras && p.codigo_barras === searchProd.value.trim()
    );
    if (exacto) {
        agregarProducto(exacto);
        searchProd.value = '';
        return;
    }

    // Si solo hay un resultado filtrado, agregarlo
    if (prodFiltrados.value.length === 1) {
        agregarProducto(prodFiltrados.value[0]);
        searchProd.value = '';
    }
}

const total = computed(() =>
    cart.value.reduce((sum, i) => sum + subtotalNumero(i), 0)
);

async function loadProductos() {
    const { data } = await axios.get(`/api/admin/sucursales/${sucId.value}/productos`);
    productos.value = data.productos.filter(p => p.activo && p.stock_actual > 0);
}

function agregarProducto(p) {
    const existing = cart.value.find(i => i.producto_id === p.id);
    if (existing) {
        existing.cantidad++;
        recalcular(existing);
    } else {
        const precio = parseFloat(p.precio_venta) || 0;
        cart.value.push({
            producto_id:     p.id,
            nombre:          p.nombre,
            codigo_barras:   p.codigo_barras,
            stock_actual:    p.stock_actual,
            cantidad:        1,
            precio_unitario: precio,
            subtotal_linea:  precio,
        });
    }
}

function quitarItem(idx) {
    cart.value.splice(idx, 1);
}

function subtotalNumero(item) {
    const v = parseNumber(item.subtotal_linea);
    if (Number.isFinite(v)) return v;
    const c = parseNumber(item.cantidad);
    const p = parseNumber(item.precio_unitario);
    return Number.isFinite(c) && Number.isFinite(p) ? c * p : 0;
}

function parseNumber(value) {
    if (value === null || value === undefined || value === '') return NaN;
    return parseFloat(String(value).replace(',', '.'));
}

function round2(value) {
    return Number(value.toFixed(2));
}

function recalcular(item) {
    const c = parseFloat(item.cantidad);
    const p = parseNumber(item.precio_unitario);
    if (!Number.isFinite(c) || c <= 0 || !Number.isFinite(p)) return;
    item.subtotal_linea = round2(c * p);
}

function onCantidadChange(item) {
    if (!Number.isFinite(parseNumber(item.cantidad)) || parseNumber(item.cantidad) <= 0) {
        item.cantidad = 1;
    }
    recalcular(item);
}

function onPrecioChange(item) {
    recalcular(item);
}

function onSubtotalChange(item, value) {
    const sub = parseNumber(value);
    const c   = parseNumber(item.cantidad);
    if (!Number.isFinite(sub) || !Number.isFinite(c) || c <= 0) return;
    item.subtotal_linea  = sub;
    item.precio_unitario = round2(sub / c);
}

async function registrarVenta() {
    errores.value = {};
    if (!cart.value.length) { errores.value.items = 'Agrega al menos un producto.'; return; }

    saving.value = true;
    try {
        await axios.post(`/api/admin/sucursales/${sucId.value}/ventas`, {
            cliente_id:  clienteId.value || null,
            metodo_pago: metodoPago.value,
            comentarios: comentarios.value || null,
            items: cart.value.map(i => ({
                producto_id:      i.producto_id,
                cantidad:         i.cantidad,
                precio_unitario:  i.precio_unitario,
            })),
        });
        cart.value        = [];
        clienteId.value   = null;
        metodoPago.value  = 'efectivo';
        comentarios.value = '';
        await loadProductos();
        await goTab('historial');
        await loadHistorial();
    } catch (e) {
        if (e.response?.status === 422)
            errores.value = e.response.data.errors ?? { general: [e.response.data.message] };
        else
            alert(e.response?.data?.message ?? 'Error al registrar venta.');
    } finally {
        saving.value = false;
    }
}

// ── Watch route / tab ─────────────────────────────────────────
watch(sucId, async () => {
    cart.value      = [];
    clienteId.value = null;
    tab.value = tabFromRoute();
    await Promise.all([loadProductos(), loadClientes(), loadHistorial()]);
}, { immediate: true });

watch(() => route.name, () => {
    tab.value = tabFromRoute();
});

watch(tab, (t) => {
    if (t === 'historial') loadHistorial();
    if (t === 'nueva') loadProductos();
});

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
        <h2 class="text-xl font-bold text-gray-800">Ventas</h2>
        <p class="text-gray-400 text-xs mt-0.5">{{ sucursal?.nombre }}</p>
      </div>
      <div class="flex gap-1 bg-gray-100 p-1 rounded-xl">
        <button @click="goTab('nueva')"
          :class="['px-4 py-1.5 rounded-lg text-xs font-semibold transition-colors',
            tab === 'nueva' ? 'bg-white text-emerald-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          Nueva Venta
        </button>
        <button @click="goTab('historial')"
          :class="['px-4 py-1.5 rounded-lg text-xs font-semibold transition-colors',
            tab === 'historial' ? 'bg-white text-emerald-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          Historial
        </button>
      </div>
    </div>

    <!-- ══════════════ TAB: NUEVA VENTA ══════════════ -->
    <div v-if="tab === 'nueva'" class="flex flex-col xl:flex-row gap-4 flex-1 min-h-0">

      <!-- Panel izquierdo: productos -->
      <div class="w-full xl:w-[58%] 2xl:w-[62%] flex-shrink-0 flex flex-col bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden min-h-[360px]">
        <div class="p-3 border-b border-gray-100">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input v-model="searchProd" type="text" placeholder="Buscar por nombre o código de barras..."
              @keyup.enter="onSearchEnter"
              class="w-full border border-gray-200 rounded-lg pl-10 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
        <div class="flex-1 overflow-y-auto p-3">
          <div v-if="!prodFiltrados.length" class="py-8 text-center text-gray-400 text-xs">Sin productos</div>
          <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5 gap-2.5">
            <button v-for="p in prodFiltrados" :key="p.id"
              @click="agregarProducto(p)"
              class="group relative h-32 overflow-hidden rounded-lg border border-gray-200 bg-gray-100 text-left shadow-sm transition hover:border-emerald-400 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
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
                  <span class="rounded bg-emerald-500 px-1.5 py-1 text-white">{{ fmtBs(p.precio_venta) }}</span>
                </div>
              </div>
              <div class="absolute right-1.5 top-1.5 rounded-full bg-emerald-600/90 p-1 text-white opacity-0 shadow transition group-hover:opacity-100">
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

        <!-- Datos de la venta -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 space-y-3">

          <!-- Selector de cliente -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Cliente (opcional)</label>
            <div class="flex gap-2">
              <select v-model="clienteId"
                class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                <option :value="null">S/N</option>
                <option v-for="cli in clientes" :key="cli.id" :value="cli.id">
                  {{ cli.nombre }}
                </option>
              </select>
              <button @click="showNuevoCli = true"
                class="flex items-center gap-1 px-3 py-1.5 border border-emerald-200 text-emerald-600 hover:bg-emerald-50 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Nuevo
              </button>
            </div>
          </div>

          <!-- Método de pago + Comentarios -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Método de pago</label>
              <select v-model="metodoPago"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="transferencia">Transferencia</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Comentarios</label>
              <input v-model="comentarios" type="text" placeholder="Opcional"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
          </div>

          <button @click="registrarVenta" :disabled="saving || !cart.length"
            class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white py-2 rounded-xl text-sm font-bold transition-colors flex items-center justify-center gap-2">
            <svg v-if="!saving" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            {{ saving ? 'Registrando...' : `Registrar Venta · ${fmtBs(total)}` }}
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
                  <th class="px-3 py-2 text-right font-semibold text-gray-400 uppercase tracking-wide w-24">Subtotal</th>
                  <th class="px-3 py-2 w-8"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!cart.length">
                  <td colspan="5" class="py-12 text-center text-gray-400">
                    <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
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
                      @input="onCantidadChange(item)"
                      class="w-full text-center border border-gray-200 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                  </td>
                  <td class="px-3 py-2">
                    <div class="flex items-center justify-end gap-1">
                      <span class="text-gray-400">Bs</span>
                      <input v-model.number="item.precio_unitario" type="number" min="0" step="0.01"
                        @input="onPrecioChange(item)"
                        class="w-20 text-right border border-gray-200 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                  </td>
                  <td class="px-3 py-2">
                    <div class="flex items-center justify-end gap-1">
                      <span class="text-gray-400">Bs</span>
                      <input v-model.number="item.subtotal_linea" type="number" min="0" step="0.01"
                        @input="onSubtotalChange(item, $event.target.value)"
                        @blur="item.subtotal_linea = round2(subtotalNumero(item))"
                        class="w-20 text-right border border-gray-200 rounded-lg px-2 py-1 text-xs font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
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
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-emerald-600 text-white rounded-xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-white/70">Ventas Completadas</p>
            <p class="text-xl font-bold leading-tight">{{ fmtBs(stats.total_completadas) }}</p>
          </div>
        </div>
        <div class="bg-red-600 text-white rounded-xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-white/70">Ventas Canceladas</p>
            <p class="text-xl font-bold leading-tight">{{ fmtBs(stats.total_canceladas) }}</p>
          </div>
        </div>
        <div class="bg-blue-600 text-white rounded-xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-white/70">Total Ventas</p>
            <p class="text-xl font-bold leading-tight">{{ stats.count }}</p>
          </div>
        </div>
        <div class="bg-amber-500 text-white rounded-xl p-4 flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
          </div>
          <div>
            <p class="text-xs text-white/70">Ganancia estimada</p>
            <p class="text-xl font-bold leading-tight">{{ fmtBs(stats.ganancia ?? 0) }}</p>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="flex flex-wrap gap-2 items-end">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Desde</label>
          <input v-model="desde" type="date"
            class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Hasta</label>
          <input v-model="hasta" type="date"
            class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
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
        <svg class="animate-spin w-6 h-6 text-emerald-500" fill="none" viewBox="0 0 24 24">
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
                <th class="px-3 py-2 text-left font-semibold tracking-wide">Folio</th>
                <th class="px-3 py-2 text-left font-semibold tracking-wide">Fecha</th>
                <th class="px-3 py-2 text-left font-semibold tracking-wide">Cliente</th>
                <th class="px-3 py-2 text-left font-semibold tracking-wide">Usuario</th>
                <th class="px-3 py-2 text-center font-semibold tracking-wide w-28">Estado</th>
                <th class="px-3 py-2 text-right font-semibold tracking-wide w-24">Total</th>
                <th class="px-3 py-2 text-left font-semibold tracking-wide">Productos</th>
                <th class="px-3 py-2 text-center font-semibold tracking-wide w-28">Pago</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!ventas.length">
                <td colspan="9" class="py-10 text-center text-gray-400">No hay ventas en este período.</td>
              </tr>
              <tr v-for="v in ventas" :key="v.id"
                :class="['border-t border-gray-50 transition-colors',
                  v.estado === 'cancelada' ? 'opacity-50 bg-red-50/30' : 'hover:bg-emerald-50/20']">
                <td class="px-3 py-2">
                  <DropdownMenu>
                    <button @click="verVenta(v)"
                      class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                      <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                      </svg>
                      Ver venta
                    </button>
                    <div v-if="v.estado === 'completada'" class="border-t border-gray-100 my-0.5"/>
                    <button v-if="v.estado === 'completada'" @click="cancelar(v)"
                      class="flex items-center gap-2 w-full px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
                      </svg>
                      Cancelar
                    </button>
                  </DropdownMenu>
                </td>
                <td class="px-3 py-2 font-mono text-gray-500 whitespace-nowrap">{{ v.folio }}</td>
                <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ fmtFecha(v.created_at) }}</td>
                <td class="px-3 py-2 font-semibold text-gray-800">{{ v.cliente?.nombre ?? 'S/N' }}</td>
                <td class="px-3 py-2 text-gray-500">{{ userName(v.usuario) }}</td>
                <td class="px-3 py-2 text-center">
                  <span :class="v.estado === 'completada' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'"
                    class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold capitalize">
                    {{ v.estado }}
                  </span>
                </td>
                <td class="px-3 py-2 text-right font-bold text-gray-800">{{ fmtBs(v.total) }}</td>
                <td class="px-3 py-2 text-gray-500 max-w-xs">
                  <p class="truncate">
                    {{ v.detalles?.map(d => `${d.cantidad}x ${d.producto?.nombre}`).join(', ') }}
                  </p>
                  <p v-if="v.comentarios" class="text-gray-400 italic truncate">{{ v.comentarios }}</p>
                </td>
                <td class="px-3 py-2 text-center">
                  <span :class="pagoColor[v.metodo_pago] ?? 'bg-gray-100 text-gray-600'"
                    class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold">
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

  <!-- ══════════════ MODAL: VER VENTA ══════════════ -->
  <Teleport to="body">
    <div v-if="ventaDetalle"
      class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="ventaDetalle = null">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
          <div>
            <h3 class="text-base font-bold text-gray-800">{{ ventaDetalle.folio }}</h3>
            <p class="text-xs text-gray-400">{{ fmtFecha(ventaDetalle.created_at) }}</p>
          </div>
          <button @click="ventaDetalle = null"
            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="p-5 space-y-4">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
              <p class="text-xs text-gray-400">Cliente</p>
              <p class="text-sm font-semibold text-gray-800">{{ ventaDetalle.cliente?.nombre ?? '— General —' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Usuario</p>
              <p class="text-sm font-semibold text-gray-800">{{ userName(ventaDetalle.usuario) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Pago</p>
              <p class="text-sm font-semibold text-gray-800">{{ pagoLabel[ventaDetalle.metodo_pago] ?? ventaDetalle.metodo_pago }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400">Total</p>
              <p class="text-sm font-bold text-gray-900">{{ fmtBs(ventaDetalle.total) }}</p>
            </div>
          </div>

          <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-xs">
              <thead class="bg-gray-50 text-gray-400 uppercase">
                <tr>
                  <th class="px-3 py-2 text-left font-semibold">Producto</th>
                  <th class="px-3 py-2 text-center font-semibold w-20">Cant.</th>
                  <th class="px-3 py-2 text-right font-semibold w-28">Unitario</th>
                  <th class="px-3 py-2 text-right font-semibold w-28">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in ventaDetalle.detalles" :key="d.id" class="border-t border-gray-50">
                  <td class="px-3 py-2 font-semibold text-gray-700">{{ d.producto?.nombre ?? 'Producto eliminado' }}</td>
                  <td class="px-3 py-2 text-center text-gray-600">{{ d.cantidad }}</td>
                  <td class="px-3 py-2 text-right text-gray-600">{{ fmtBs(d.precio_unitario) }}</td>
                  <td class="px-3 py-2 text-right font-bold text-gray-800">{{ fmtBs(d.subtotal ?? (d.cantidad * d.precio_unitario)) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="ventaDetalle.comentarios" class="rounded-xl bg-gray-50 px-3 py-2">
            <p class="text-xs text-gray-400">Comentarios</p>
            <p class="text-sm text-gray-700">{{ ventaDetalle.comentarios }}</p>
          </div>

          <div class="flex justify-end">
            <div class="text-right">
              <p class="text-xs text-gray-400">Total</p>
              <p class="text-lg font-bold text-gray-900">{{ fmtBs(ventaDetalle.total) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- ══════════════ MODAL: NUEVO CLIENTE ══════════════ -->
  <Teleport to="body">
    <div v-if="showNuevoCli"
      class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="showNuevoCli = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-bold text-gray-800">Nuevo Cliente</h3>
          <button @click="showNuevoCli = false"
            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre *</label>
            <input v-model="nuevoCli.nombre" type="text" placeholder="Nombre del cliente"
              :class="['w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500',
                erroresCli.nombre ? 'border-red-400' : 'border-gray-200']"
              @keyup.enter="guardarNuevoCliente">
            <p v-if="erroresCli.nombre" class="text-red-500 text-xs mt-0.5">
              {{ Array.isArray(erroresCli.nombre) ? erroresCli.nombre[0] : erroresCli.nombre }}
            </p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Teléfono</label>
            <input v-model="nuevoCli.telefono" type="text" placeholder="Ej: 71234567"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
            <input v-model="nuevoCli.email" type="email" placeholder="correo@ejemplo.com"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <div class="flex gap-2 mt-5">
          <button @click="showNuevoCli = false"
            class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors">
            Cancelar
          </button>
          <button @click="guardarNuevoCliente" :disabled="savingCli"
            class="flex-1 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white py-2 rounded-xl text-sm font-bold transition-colors">
            {{ savingCli ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
