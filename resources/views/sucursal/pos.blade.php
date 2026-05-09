@extends('layouts.sucursal')
@section('titulo', 'Punto de Venta')

@section('extra-styles')
.pos-product-btn {
    background: white; border: 1px solid #e2e8f0; border-radius: 6px;
    padding: 10px; text-align: left; cursor: pointer; transition: all 0.15s;
    display: flex; flex-direction: column;
}
.pos-product-btn:hover { background: #eff6ff; border-color: #3b82f6; box-shadow: 0 2px 8px rgba(59,130,246,0.15); }
.pos-total { font-size: 2.5rem; font-weight: 800; color: #1e3a5f; font-family: 'Courier New', monospace; }
.search-bar { background: white; border: 2px solid #3b82f6; border-radius: 6px; padding: 10px 14px; font-size: 16px; width: 100%; }
.search-bar:focus { outline: none; box-shadow: 0 0 0 3px rgba(59,130,246,0.25); }
.tab-btn { padding: 6px 16px; border-radius: 4px 4px 0 0; font-weight: 500; font-size: 13px; cursor: pointer; border: 1px solid #e2e8f0; border-bottom: none; background: #f1f5f9; color: #64748b; }
.tab-btn.active { background: white; color: #1e3a5f; font-weight: 700; border-color: #cbd5e1; }
.payment-btn { padding: 12px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; border: 2px solid transparent; transition: all 0.15s; width: 100%; text-align: center; }
.payment-btn.selected { border-color: #3b82f6 !important; box-shadow: 0 0 0 2px rgba(59,130,246,0.3); }

/* ── IMPRESIÓN TICKET (tamaño rollo 80mm) ──────────── */
@media print {
    * { visibility: hidden; }
    #ticket-print-content, #ticket-print-content * { visibility: visible; }
    #ticket-print-content {
        position: fixed; left: 0; top: 0;
        width: 80mm; max-width: 80mm;
        font-family: 'Courier New', monospace;
        font-size: 11px; line-height: 1.4;
        padding: 4mm;
    }
    body { margin: 0; padding: 0; background: white; }
}
@endsection

@section('content')
<div class="section-bar flex items-center justify-between">
    <span class="font-bold text-blue-900 text-lg">VENTA - Ticket 1</span>
    <span class="text-sm text-gray-600"><i class="fas fa-calendar-alt mr-1"></i>{{ now()->format('d/m/Y H:i') }}</span>
</div>

<div class="p-3" style="padding-bottom: 60px;">
    <div class="flex gap-3" style="min-height: calc(100vh - 200px);">

        <!-- PANEL IZQUIERDO -->
        <div class="flex-1 flex flex-col gap-3">
            <!-- Barra de búsqueda -->
            <div class="card p-3">
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input type="text" id="buscar-producto" placeholder="Código de barras o nombre del producto..."
                            class="search-bar pr-10" autocomplete="off" autofocus>
                        <i class="fas fa-barcode absolute right-3 top-3 text-gray-400 text-lg"></i>
                    </div>
                    <button onclick="buscarProductos()" class="btn-primary px-4"><i class="fas fa-search"></i> ENTER</button>
                    <button onclick="abrirBusquedaAvanzada()" class="btn-secondary px-3" title="F10 Buscar"><i class="fas fa-search-plus"></i> F10</button>
                </div>
                <div class="flex gap-2 mt-2 flex-wrap">
                    <button onclick="abrirBusquedaAvanzada()" class="nav-btn"><i class="fas fa-search text-green-500"></i> INS Varios</button>
                    <button onclick="toggleMayoreo()" class="nav-btn" id="btn-mayoreo"><i class="fas fa-boxes text-purple-500"></i> F11 Mayoreo</button>
                    <button onclick="window.location.href='/{{ $sucursal->slug }}/inventario'" class="nav-btn"><i class="fas fa-arrow-circle-down text-teal-500"></i> F7 Entradas</button>
                    <button onclick="window.location.href='/{{ $sucursal->slug }}/inventario'" class="nav-btn"><i class="fas fa-arrow-circle-up text-orange-500"></i> F8 Salidas</button>
                    <button onclick="borrarUltimoItem()" class="nav-btn"><i class="fas fa-trash text-red-500"></i> DEL Borrar Art.</button>
                    <button onclick="abrirBusquedaAvanzada()" class="nav-btn"><i class="fas fa-check-circle text-green-500"></i> F9 - Verificador</button>
                </div>
            </div>

            <!-- Tabla del ticket -->
            <div class="card flex-1">
                <div class="flex px-3 pt-2 gap-1 border-b border-gray-200">
                    <div class="tab-btn active"><i class="fas fa-file-alt mr-1"></i> Ticket 1</div>
                </div>
                <div class="grid text-xs font-bold text-gray-600 px-3 py-2 table-header border-b border-gray-200"
                     style="grid-template-columns: 130px 1fr 110px 90px 110px 90px 40px;">
                    <span>Código Barras</span><span>Descripción del Producto</span>
                    <span class="text-right">Precio Venta</span><span class="text-center">Cant.</span>
                    <span class="text-right">Importe</span><span class="text-right">Existencia</span><span></span>
                </div>
                <div id="ticket-items" class="overflow-y-auto" style="max-height: 340px;">
                    <div id="ticket-vacio" class="flex flex-col items-center justify-center py-16 text-gray-400">
                        <i class="fas fa-shopping-cart text-4xl mb-3 opacity-30"></i>
                        <p class="text-sm">Escanee o busque un producto para comenzar</p>
                    </div>
                </div>
            </div>

            <!-- Barra inferior -->
            <div class="card p-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span id="total-items-count" class="text-2xl font-black text-blue-900">0</span>
                    <span class="text-sm text-gray-500">Productos en la venta actual.</span>
                </div>
                <div class="flex gap-2">
                    <button onclick="guardarPendiente()" class="nav-btn"><i class="fas fa-pause text-yellow-500"></i> F6 - Pendiente</button>
                    <button onclick="eliminarTicket()" class="nav-btn"><i class="fas fa-times text-red-500"></i> Eliminar</button>
                    <button onclick="asignarCliente()" class="nav-btn"><i class="fas fa-user-plus text-green-500"></i> Asignar cliente</button>
                </div>
            </div>
        </div>

        <!-- PANEL DERECHO: Cobro -->
        <div class="flex flex-col gap-3" style="width: 280px;">
            <div class="card p-4 text-center">
                <div class="text-xs text-gray-500 uppercase font-bold tracking-wide mb-1">Total a Cobrar</div>
                <div class="pos-total" id="display-total">Bs. 0.00</div>
            </div>

            <button onclick="abrirModalCobro()" class="w-full py-5 rounded-lg font-black text-xl text-white shadow-lg"
                style="background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%); border: 2px solid #15803d;">
                <i class="fas fa-dollar-sign mr-2"></i> F12 - Cobrar
            </button>

            <div class="card p-3 text-sm">
                <div class="flex justify-between py-1 border-b border-gray-100">
                    <span class="text-gray-600">Total:</span><span class="font-bold" id="info-total">Bs. 0.00</span>
                </div>
                <div class="flex justify-between py-1 border-b border-gray-100">
                    <span class="text-gray-600">Pagó Con:</span><span class="font-bold text-blue-700" id="info-pago">Bs. 0.00</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-gray-600">Cambio:</span><span class="font-bold text-green-700" id="info-cambio">Bs. 0.00</span>
                </div>
            </div>

            <div class="card p-3 flex flex-col gap-2">
                <button onclick="window.location.href='/{{ $sucursal->slug }}/ventas'" class="nav-btn justify-center">
                    <i class="fas fa-print text-blue-500"></i> Reimprimir Último Ticket
                </button>
                <button onclick="window.location.href='/{{ $sucursal->slug }}/ventas'" class="nav-btn justify-center">
                    <i class="fas fa-list text-teal-500"></i> Ventas del día y Devoluciones
                </button>
            </div>

            <!-- Productos rápidos -->
            <div class="card p-3 flex-1">
                <div class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">Productos Frecuentes</div>
                <div class="grid grid-cols-2 gap-2 overflow-y-auto" style="max-height: 220px;">
                    @foreach($productos->take(12) as $prod)
                    <button onclick="agregarProducto({{ $prod->id }}, '{{ addslashes($prod->nombre) }}', {{ $prod->precio_venta }}, {{ $prod->stock_actual }}, '{{ addslashes($prod->codigo_barras ?? '') }}')"
                        class="pos-product-btn">
                        <span class="text-xs font-bold text-gray-800 leading-tight">{{ Str::limit($prod->nombre, 20) }}</span>
                        <span class="text-green-700 font-bold text-sm mt-1">Bs. {{ number_format($prod->precio_venta, 2) }}</span>
                        <span class="text-xs text-gray-400">Stock: {{ $prod->stock_actual }}</span>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL COBRO -->
<div id="modal-cobro" class="modal-overlay hidden">
    <div class="modal-box" style="min-width: 460px;">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold"><i class="fas fa-cash-register mr-2 text-green-600"></i>Procesar Cobro</h2>
            <button onclick="cerrarModalCobro()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="text-center mb-4 bg-blue-50 rounded-lg p-4">
            <div class="text-sm text-gray-500 mb-1">Total a cobrar</div>
            <div class="text-4xl font-black text-blue-900" id="cobro-total">Bs. 0.00</div>
        </div>
        <div class="mb-4">
            <div class="text-sm font-bold text-gray-600 mb-2">Forma de Pago:</div>
            <div class="grid grid-cols-3 gap-2">
                <button onclick="seleccionarPago('efectivo')" class="payment-btn" id="pago-efectivo" style="background:#dcfce7;color:#166534;border-color:#86efac;">
                    <i class="fas fa-money-bill-wave text-xl block mb-1"></i>Efectivo
                </button>
                <button onclick="seleccionarPago('tarjeta')" class="payment-btn" id="pago-tarjeta" style="background:#dbeafe;color:#1e40af;border-color:#93c5fd;">
                    <i class="fas fa-credit-card text-xl block mb-1"></i>Tarjeta
                </button>
                <button onclick="seleccionarPago('transferencia')" class="payment-btn" id="pago-transferencia" style="background:#f3e8ff;color:#6b21a8;border-color:#d8b4fe;">
                    <i class="fas fa-mobile-alt text-xl block mb-1"></i>QR/Transfer
                </button>
            </div>
        </div>
        <div id="div-monto-efectivo" class="mb-4">
            <label class="text-sm font-bold text-gray-600 block mb-1">Monto Recibido:</label>
            <input type="number" id="monto-recibido" class="input-field text-right text-2xl font-bold" placeholder="0.00" oninput="calcularCambio()" step="0.01" min="0">
            <div class="grid grid-cols-4 gap-2 mt-2">
                <button onclick="setMonto(20)" class="nav-btn justify-center">Bs. 20</button>
                <button onclick="setMonto(50)" class="nav-btn justify-center">Bs. 50</button>
                <button onclick="setMonto(100)" class="nav-btn justify-center">Bs. 100</button>
                <button onclick="setMonto('exacto')" class="nav-btn justify-center">Exacto</button>
            </div>
        </div>
        <div id="div-cambio" class="mb-4 bg-green-50 rounded-lg p-3 text-center hidden">
            <div class="text-sm text-green-700 font-bold">Cambio a entregar</div>
            <div class="text-3xl font-black text-green-800" id="display-cambio">Bs. 0.00</div>
        </div>
        <div class="flex gap-3">
            <button onclick="cerrarModalCobro()" class="btn-secondary flex-1"><i class="fas fa-times mr-2"></i>Cancelar</button>
            <button onclick="procesarVenta()" class="btn-success flex-1 py-3 text-lg"><i class="fas fa-check mr-2"></i>Confirmar Cobro</button>
        </div>
    </div>
</div>

<!-- MODAL BÚSQUEDA AVANZADA -->
<div id="modal-busqueda" class="modal-overlay hidden">
    <div class="modal-box" style="min-width: 700px;">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">🔍 Buscar Producto</h2>
            <button onclick="document.getElementById('modal-busqueda').classList.add('hidden')" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <input type="text" id="busqueda-avanzada-input" placeholder="Buscar por nombre o código..." class="input-field mb-3" oninput="debounceFiltrar()">
        <div class="overflow-y-auto" style="max-height: 400px;">
            <table class="w-full text-sm">
                <thead class="table-header sticky top-0"><tr>
                    <th class="text-left p-2">Código</th><th class="text-left p-2">Nombre</th>
                    <th class="text-right p-2">Precio</th><th class="text-right p-2">Stock</th><th class="p-2"></th>
                </tr></thead>
                <tbody id="tabla-productos-busqueda">
                    @foreach($productos as $prod)
                    <tr class="table-row border-b" data-nombre="{{ strtolower($prod->nombre) }}" data-codigo="{{ $prod->codigo_barras ?? '' }}">
                        <td class="p-2 text-gray-500 font-mono text-xs">{{ $prod->codigo_barras ?? '-' }}</td>
                        <td class="p-2 font-medium">{{ $prod->nombre }}</td>
                        <td class="p-2 text-right text-green-700 font-bold">Bs. {{ number_format($prod->precio_venta, 2) }}</td>
                        <td class="p-2 text-right {{ $prod->stock_actual <= ($prod->stock_minimo ?? 5) ? 'text-red-600 font-bold' : 'text-gray-700' }}">{{ $prod->stock_actual }}</td>
                        <td class="p-2">
                            <button onclick="agregarProducto({{ $prod->id }}, '{{ addslashes($prod->nombre) }}', {{ $prod->precio_venta }}, {{ $prod->stock_actual }}, '{{ addslashes($prod->codigo_barras ?? '') }}'); document.getElementById('modal-busqueda').classList.add('hidden');" class="btn-primary px-3 py-1 text-xs">Agregar</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TICKET -->
<div id="modal-ticket" class="modal-overlay hidden">
    <div class="bg-white rounded-lg shadow-xl p-6" style="width: 340px;">
        <div id="ticket-print-content" class="text-center font-mono">
            <div class="font-black text-xl mb-1">🥃 ELIXIRDORADO</div>
            <div class="text-sm">{{ $sucursal->nombre }}</div>
            <div class="text-xs text-gray-500">{{ $sucursal->direccion ?? 'Bolivia' }}</div>
            <div class="border-t border-dashed my-3"></div>
            <div class="text-xs text-left" id="ticket-items-print"></div>
            <div class="border-t border-dashed my-3"></div>
            <div class="text-right text-lg font-black" id="ticket-total-print"></div>
            <div class="text-xs text-gray-500 mt-1" id="ticket-pago-print"></div>
            <div class="border-t border-dashed my-3"></div>
            <div class="text-xs text-gray-500">¡Gracias por su compra!</div>
        </div>
        <div class="flex gap-2 mt-4">
            <button onclick="window.print()" class="btn-primary flex-1"><i class="fas fa-print mr-1"></i>Imprimir</button>
            <button onclick="document.getElementById('modal-ticket').classList.add('hidden')" class="btn-secondary flex-1">Cerrar</button>
        </div>
    </div>
</div>
@endsection

@section('keyboard-shortcuts')
if (e.key === 'F12') { e.preventDefault(); abrirModalCobro(); return; }
if (e.key === 'F10') { e.preventDefault(); abrirBusquedaAvanzada(); return; }
if (e.key === 'Delete' && !['INPUT','TEXTAREA'].includes(document.activeElement.tagName)) { e.preventDefault(); borrarUltimoItem(); return; }
if (e.key === 'Escape') { cerrarModalCobro(); document.getElementById('modal-busqueda').classList.add('hidden'); }
@endsection

@section('scripts')
<script>
let carrito = [];
let metodoPago = 'efectivo';
let modoMayoreo = false;
const productosDB = @json($productos);

document.getElementById('buscar-producto').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const codigo = this.value.trim();
        if (!codigo) { abrirBusquedaAvanzada(); return; }
        const prod = productosDB.find(p => p.codigo_barras == codigo || String(p.id) == codigo);
        if (prod) {
            agregarProducto(prod.id, prod.nombre, prod.precio_venta, prod.stock_actual, prod.codigo_barras || '');
            this.value = '';
        } else {
            document.getElementById('busqueda-avanzada-input').value = codigo;
            filtrarTablaProductos();
            document.getElementById('modal-busqueda').classList.remove('hidden');
        }
        this.focus();
    }
});

function buscarProductos() {
    const q = document.getElementById('buscar-producto').value.trim();
    document.getElementById('busqueda-avanzada-input').value = q;
    filtrarTablaProductos();
    document.getElementById('modal-busqueda').classList.remove('hidden');
}

function filtrarTablaProductos() {
    const q = document.getElementById('busqueda-avanzada-input').value.toLowerCase();
    document.querySelectorAll('#tabla-productos-busqueda tr').forEach(row => {
        const ok = (row.dataset.nombre || '').includes(q) || (row.dataset.codigo || '').includes(q);
        row.style.display = ok ? '' : 'none';
    });
}

// Debounce de 250 ms — alivia CPU en PCs antiguas con catálogos grandes
let _filtroTimer = null;
function debounceFiltrar() {
    clearTimeout(_filtroTimer);
    _filtroTimer = setTimeout(filtrarTablaProductos, 250);
}

function agregarProducto(id, nombre, precio, stock, codigo) {
    let p = parseFloat(precio);
    if (!isFinite(p) || p <= 0) { mostrarAlerta('Producto sin precio válido', 'error'); return; }
    if (modoMayoreo) p = parseFloat((p * 0.9).toFixed(2));
    let item = carrito.find(i => i.id === id);
    if (item) {
        if (item.cantidad + 1 > stock) { mostrarAlerta('Stock insuficiente', 'error'); return; }
        item.cantidad++;
        item.subtotal = parseFloat((item.cantidad * item.precio).toFixed(2));
    } else {
        if (stock < 1) { mostrarAlerta('Sin stock disponible', 'error'); return; }
        carrito.push({ id, nombre, precio: p, cantidad: 1, subtotal: p, stock: parseInt(stock), codigo: codigo || '' });
    }
    renderizarCarrito();
    document.getElementById('buscar-producto').focus();
}

function renderizarCarrito() {
    const container = document.getElementById('ticket-items');
    if (carrito.length === 0) {
        container.innerHTML = `<div class="flex flex-col items-center justify-center py-16 text-gray-400">
            <i class="fas fa-shopping-cart text-4xl mb-3 opacity-30"></i>
            <p class="text-sm">Escanee o busque un producto para comenzar</p></div>`;
        document.getElementById('total-items-count').textContent = '0';
        actualizarTotal(); return;
    }
    container.innerHTML = carrito.map((item, idx) => `
        <div class="grid items-center px-3 py-2 table-row border-b border-gray-100 text-sm"
             style="grid-template-columns: 130px 1fr 110px 90px 110px 90px 40px;">
            <span class="text-gray-400 font-mono text-xs truncate">${item.codigo || '-'}</span>
            <span class="font-medium text-gray-800 truncate">${item.nombre}</span>
            <span class="text-right text-gray-700">Bs. ${item.precio.toFixed(2)}</span>
            <div class="flex items-center justify-center gap-1">
                <button onclick="cambiarCantidad(${idx},-1)" class="w-5 h-5 bg-red-100 text-red-700 rounded text-xs font-bold hover:bg-red-200">-</button>
                <span class="w-8 text-center font-bold text-xs">${item.cantidad}</span>
                <button onclick="cambiarCantidad(${idx},1)" class="w-5 h-5 bg-green-100 text-green-700 rounded text-xs font-bold hover:bg-green-200">+</button>
            </div>
            <span class="text-right font-bold text-blue-900">Bs. ${item.subtotal.toFixed(2)}</span>
            <span class="text-right text-gray-500 text-xs">${item.stock}</span>
            <button onclick="eliminarItem(${idx})" class="text-red-400 hover:text-red-600 text-center"><i class="fas fa-times text-xs"></i></button>
        </div>`).join('');
    document.getElementById('total-items-count').textContent = carrito.reduce((s,i) => s+i.cantidad, 0);
    actualizarTotal();
}

function cambiarCantidad(idx, delta) {
    const item = carrito[idx];
    const n = item.cantidad + delta;
    if (n < 1) { eliminarItem(idx); return; }
    if (n > item.stock) { mostrarAlerta('Stock máximo: ' + item.stock, 'error'); return; }
    item.cantidad = n; item.subtotal = parseFloat((n * item.precio).toFixed(2));
    renderizarCarrito();
}

function eliminarItem(idx) { carrito.splice(idx, 1); renderizarCarrito(); }
function borrarUltimoItem() { if (carrito.length > 0) { carrito.pop(); renderizarCarrito(); } }

function actualizarTotal() {
    const total = carrito.reduce((s,i) => s+i.subtotal, 0);
    const fmt = 'Bs. ' + total.toFixed(2);
    ['display-total','info-total','cobro-total'].forEach(id => { const el = document.getElementById(id); if(el) el.textContent = fmt; });
}

function abrirModalCobro() {
    if (carrito.length === 0) { mostrarAlerta('Agregue productos antes de cobrar', 'error'); return; }
    document.getElementById('modal-cobro').classList.remove('hidden');
    seleccionarPago('efectivo');
    setTimeout(() => document.getElementById('monto-recibido').focus(), 100);
}
function cerrarModalCobro() { document.getElementById('modal-cobro').classList.add('hidden'); }

function seleccionarPago(metodo) {
    metodoPago = metodo;
    ['efectivo','tarjeta','transferencia'].forEach(m => document.getElementById('pago-'+m).classList.remove('selected'));
    document.getElementById('pago-'+metodo).classList.add('selected');
    document.getElementById('div-monto-efectivo').style.display = metodo === 'efectivo' ? '' : 'none';
    if (metodo !== 'efectivo') document.getElementById('div-cambio').classList.add('hidden');
}

function calcularCambio() {
    const total = carrito.reduce((s,i) => s+i.subtotal, 0);
    const rec = parseFloat(document.getElementById('monto-recibido').value) || 0;
    const cambio = rec - total;
    document.getElementById('display-cambio').textContent = 'Bs. ' + Math.max(0, cambio).toFixed(2);
    document.getElementById('info-pago').textContent = 'Bs. ' + rec.toFixed(2);
    document.getElementById('info-cambio').textContent = 'Bs. ' + Math.max(0, cambio).toFixed(2);
    document.getElementById('div-cambio').classList.toggle('hidden', rec <= 0);
}

function setMonto(v) {
    const total = carrito.reduce((s,i) => s+i.subtotal, 0);
    document.getElementById('monto-recibido').value = v === 'exacto' ? total.toFixed(2) : v;
    calcularCambio();
}

let ventaEnProceso = false;
async function procesarVenta() {
    if (ventaEnProceso) return;                   // anti-doble-submit
    if (carrito.length === 0) return;

    const total = carrito.reduce((s,i) => s+i.subtotal, 0);
    const monto = parseFloat(document.getElementById('monto-recibido').value) || total;
    if (metodoPago === 'efectivo' && monto < total - 0.01) { mostrarAlerta('Monto insuficiente', 'error'); return; }

    const btnConfirmar = document.querySelector('#modal-cobro .btn-success');
    ventaEnProceso = true;
    if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.dataset.original = btnConfirmar.innerHTML;
        btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando...';
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const payload = {
        items: carrito.map(i => ({producto_id:i.id, cantidad:i.cantidad, precio:i.precio})),
        total, metodo_pago: metodoPago,
    };
    try {
        const res = await fetch('/{{ $sucursal->slug }}/pos/venta', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success) {
            cerrarModalCobro();
            mostrarTicket(data, monto);
        } else {
            mostrarAlerta(data.message || 'Error al procesar la venta', 'error');
        }
    } catch (e) {
        mostrarAlerta('Error de conexión al servidor. Verifique e intente de nuevo.', 'error');
    } finally {
        ventaEnProceso = false;
        if (btnConfirmar) {
            btnConfirmar.disabled = false;
            if (btnConfirmar.dataset.original) btnConfirmar.innerHTML = btnConfirmar.dataset.original;
        }
    }
}

function mostrarTicket(data, montoRec) {
    const items = carrito.map(i => `<div class="flex justify-between py-0.5 text-xs"><span>${i.nombre} x${i.cantidad}</span><span>Bs.${i.subtotal.toFixed(2)}</span></div>`).join('');
    document.getElementById('ticket-items-print').innerHTML = items;
    document.getElementById('ticket-total-print').innerHTML = `TOTAL: Bs. ${data.total.toFixed(2)}`;
    document.getElementById('ticket-pago-print').innerHTML = `Folio: ${data.folio} | ${metodoPago} | Rec: Bs.${montoRec.toFixed(2)} | Cambio: Bs.${Math.max(0,montoRec-data.total).toFixed(2)}`;
    document.getElementById('modal-ticket').classList.remove('hidden');
    carrito = []; renderizarCarrito();
}

function abrirBusquedaAvanzada() {
    document.getElementById('modal-busqueda').classList.remove('hidden');
    setTimeout(() => document.getElementById('busqueda-avanzada-input').focus(), 100);
}

function toggleMayoreo() {
    modoMayoreo = !modoMayoreo;
    document.getElementById('btn-mayoreo').style.background = modoMayoreo ? '#fef08a' : '';
    mostrarAlerta(modoMayoreo ? 'Modo mayoreo activado (-10%)' : 'Modo mayoreo desactivado', 'info');
}

function mostrarAlerta(msg, tipo='info') {
    const colores = {success:'bg-green-500', error:'bg-red-500', info:'bg-blue-500'};
    const el = document.createElement('div');
    el.className = `fixed top-20 right-4 ${colores[tipo]||'bg-gray-500'} text-white px-4 py-3 rounded-lg shadow-xl z-[100] text-sm font-medium`;
    el.innerHTML = `<i class="fas fa-info-circle mr-2"></i>${msg}`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
}

function guardarPendiente() { mostrarAlerta('Ticket guardado como pendiente', 'success'); }
function eliminarTicket() { if (confirm('¿Eliminar el ticket actual?')) { carrito = []; renderizarCarrito(); } }
function asignarCliente() { window.location.href = '/{{ $sucursal->slug }}/clientes'; }
</script>
@endsection
