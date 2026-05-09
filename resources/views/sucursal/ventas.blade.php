@extends('layouts.sucursal')
@section('titulo', 'Ventas')

@section('extra-styles')
@media print {
    .no-print, nav, .topbar, .module-bar, .section-bar, .status-bar { display: none !important; }
    body { background: white !important; }
    .print-area { display: block !important; }
    table { font-size: 11px; }
}
@endsection

@section('content')
<div class="section-bar flex items-center justify-between">
    <span class="font-bold text-blue-900 text-lg">VENTAS DEL DÍA</span>
    <button onclick="window.print()" class="btn-secondary text-sm no-print">
        <i class="fas fa-print mr-1"></i>Imprimir
    </button>
</div>

<div class="p-4 no-print" style="padding-bottom:60px;">

    <!-- Tabs -->
    <div class="flex gap-0 mb-4 border-b border-gray-300">
        <button onclick="cambiarTab('ticket')" id="tab-ticket"
            class="px-5 py-2 text-sm font-semibold border-b-2 border-blue-600 text-blue-700 bg-white">Ventas por Ticket</button>
        <button onclick="cambiarTab('global')" id="tab-global"
            class="px-5 py-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700">Ventas Globales</button>
        <button onclick="cambiarTab('devolucion')" id="tab-devolucion"
            class="px-5 py-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700">Devoluciones</button>
    </div>

    <!-- Filtros -->
    <div class="card p-3 mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="text-xs text-gray-500 block mb-1">Desde:</label>
            <input type="date" id="f-desde" class="input-field" style="width:150px;">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Hasta:</label>
            <input type="date" id="f-hasta" class="input-field" style="width:150px;">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Estado:</label>
            <select id="f-estado" class="input-field" style="width:130px;" onchange="filtrarVentas()">
                <option value="">Todos</option>
                <option value="completada">Completada</option>
                <option value="cancelada">Cancelada</option>
                <option value="pendiente">Pendiente</option>
            </select>
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Método Pago:</label>
            <select id="f-metodo" class="input-field" style="width:140px;" onchange="filtrarVentas()">
                <option value="">Todos</option>
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="transferencia">Transferencia</option>
            </select>
        </div>
        <div class="flex-1">
            <label class="text-xs text-gray-500 block mb-1">Buscar:</label>
            <input type="text" id="f-buscar" placeholder="Buscar folio..." class="input-field" oninput="filtrarVentas()">
        </div>
        <button onclick="filtrarVentas()" class="btn-primary px-4 py-2">
            <i class="fas fa-search mr-1"></i>Buscar
        </button>
        <a href="/{{ $sucursal->slug }}/ventas/excel" class="btn-secondary px-4 py-2 inline-flex items-center">
            <i class="fas fa-file-excel mr-1 text-green-600"></i>Exportar a Excel
        </a>
    </div>

    <!-- Cards de resumen -->
    <div class="grid grid-cols-3 gap-4 mb-4" id="resumen-cards">
        <div class="card p-4 border-l-4 border-blue-500">
            <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Total del Período</div>
            <div class="text-2xl font-black text-blue-900 mt-1" id="r-total">
                Bs. {{ number_format($ventas->sum('total'), 2) }}
            </div>
        </div>
        <div class="card p-4 border-l-4 border-green-500">
            <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Número de Ventas</div>
            <div class="text-2xl font-black text-green-900 mt-1" id="r-count">{{ $ventas->count() }}</div>
        </div>
        <div class="card p-4 border-l-4 border-purple-500">
            <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Promedio por Venta</div>
            <div class="text-2xl font-black text-purple-900 mt-1" id="r-promedio">
                Bs. {{ $ventas->count() > 0 ? number_format($ventas->sum('total') / $ventas->count(), 2) : '0.00' }}
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="table-header">
                <tr>
                    <th class="text-left p-3">Fecha</th>
                    <th class="text-left p-3">Folio</th>
                    <th class="text-left p-3">Método Pago</th>
                    <th class="text-right p-3">Subtotal</th>
                    <th class="text-right p-3">IVA</th>
                    <th class="text-right p-3">Total</th>
                    <th class="text-center p-3">Estado</th>
                    <th class="text-center p-3">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-ventas">
                @forelse($ventas as $v)
                <tr class="table-row border-b venta-row"
                    data-estado="{{ $v->estado }}"
                    data-metodo="{{ $v->metodo_pago }}"
                    data-folio="{{ strtolower($v->folio) }}">
                    <td class="p-3 text-gray-600">{{ \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y H:i') }}</td>
                    <td class="p-3 font-mono text-xs font-bold">{{ $v->folio }}</td>
                    <td class="p-3">
                        @if($v->metodo_pago === 'efectivo')
                            <span class="badge-green"><i class="fas fa-money-bill-wave mr-1"></i>Efectivo</span>
                        @elseif($v->metodo_pago === 'tarjeta')
                            <span class="badge-blue"><i class="fas fa-credit-card mr-1"></i>Tarjeta</span>
                        @else
                            <span class="badge-yellow"><i class="fas fa-mobile-alt mr-1"></i>QR/Transfer</span>
                        @endif
                    </td>
                    <td class="p-3 text-right text-gray-700">Bs. {{ number_format($v->subtotal ?? 0, 2) }}</td>
                    <td class="p-3 text-right text-gray-500">Bs. {{ number_format($v->iva ?? 0, 2) }}</td>
                    <td class="p-3 text-right font-bold text-gray-900">Bs. {{ number_format($v->total, 2) }}</td>
                    <td class="p-3 text-center">
                        @if($v->estado === 'completada')
                            <span class="badge-green">Completada</span>
                        @elseif($v->estado === 'cancelada')
                            <span class="badge-red">Cancelada</span>
                        @else
                            <span class="badge-yellow">{{ ucfirst($v->estado) }}</span>
                        @endif
                    </td>
                    <td class="p-3 text-center">
                        <button onclick="verDetalle({{ $v->id }}, '{{ $v->folio }}')"
                            class="btn-secondary px-3 py-1 text-xs">
                            <i class="fas fa-eye mr-1"></i>Ver detalle
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-16 text-gray-400">
                        <i class="fas fa-receipt text-4xl mb-3 block opacity-30"></i>
                        <p>No hay ventas registradas aún.</p>
                        <a href="/{{ $sucursal->slug }}/pos" class="text-blue-600 hover:underline mt-2 block">Ir al Punto de Venta →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer con totales -->
        @if($ventas->count() > 0)
        <div class="border-t bg-gray-50 px-3 py-2 flex justify-between text-sm font-bold">
            <span class="text-gray-600">TOTALES ({{ $ventas->count() }} ventas)</span>
            <div class="flex gap-8">
                <span class="text-gray-500">Bs. {{ number_format($ventas->sum('subtotal'), 2) }}</span>
                <span class="text-gray-500">Bs. {{ number_format($ventas->sum('iva'), 2) }}</span>
                <span class="text-green-700">Bs. {{ number_format($ventas->sum('total'), 2) }}</span>
                <span class="w-24"></span>
                <span class="w-24"></span>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- MODAL DETALLE DE VENTA -->
<div id="modal-detalle" class="modal-overlay hidden no-print">
    <div class="modal-box" style="min-width:600px; max-width:700px;">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" id="modal-detalle-titulo">
                <i class="fas fa-receipt mr-2 text-blue-600"></i>Detalle de Venta
            </h2>
            <button onclick="cerrarDetalle()" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>

        <!-- Info venta -->
        <div id="detalle-info" class="grid grid-cols-3 gap-3 mb-4 text-sm">
            <div class="bg-gray-50 rounded p-3">
                <div class="text-xs text-gray-500">Folio</div>
                <div class="font-bold font-mono" id="d-folio">-</div>
            </div>
            <div class="bg-gray-50 rounded p-3">
                <div class="text-xs text-gray-500">Fecha</div>
                <div class="font-bold" id="d-fecha">-</div>
            </div>
            <div class="bg-gray-50 rounded p-3">
                <div class="text-xs text-gray-500">Método de Pago</div>
                <div class="font-bold capitalize" id="d-metodo">-</div>
            </div>
        </div>

        <!-- Items -->
        <table class="w-full text-sm mb-4">
            <thead class="table-header">
                <tr>
                    <th class="text-left p-2">Código</th>
                    <th class="text-left p-2">Producto</th>
                    <th class="text-center p-2">Cantidad</th>
                    <th class="text-right p-2">Precio Unit.</th>
                    <th class="text-right p-2">Subtotal</th>
                </tr>
            </thead>
            <tbody id="d-items"></tbody>
        </table>

        <!-- Totales -->
        <div class="border-t pt-3 text-sm">
            <div class="flex justify-between py-1"><span class="text-gray-600">Subtotal:</span><span id="d-subtotal" class="font-medium">-</span></div>
            <div class="flex justify-between py-1"><span class="text-gray-600">IVA:</span><span id="d-iva" class="font-medium">-</span></div>
            <div class="flex justify-between py-1 text-lg font-bold"><span>TOTAL:</span><span id="d-total" class="text-green-700">-</span></div>
        </div>

        <div class="flex gap-3 mt-4">
            <button onclick="imprimirDetalle()" class="btn-secondary flex-1">
                <i class="fas fa-print mr-2"></i>Imprimir Ticket
            </button>
            <button id="btn-cancelar-venta" onclick="" class="btn-danger flex-1">
                <i class="fas fa-ban mr-2"></i>Cancelar Venta
            </button>
            <button onclick="cerrarDetalle()" class="btn-primary flex-1">
                <i class="fas fa-times mr-2"></i>Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ÁREA DE IMPRESIÓN TICKET -->
<div id="print-area" style="display:none;" class="print-area">
    <div style="font-family:monospace; max-width:300px; margin:0 auto; font-size:12px;">
        <div style="text-align:center; font-size:16px; font-weight:bold; margin-bottom:4px;">🥃 ELIXIRDORADO</div>
        <div style="text-align:center;">{{ $sucursal->nombre }}</div>
        <div style="text-align:center; font-size:10px; color:#666;">{{ $sucursal->direccion ?? 'Bolivia' }}</div>
        <div style="border-top:1px dashed #000; margin:8px 0;"></div>
        <div id="print-items"></div>
        <div style="border-top:1px dashed #000; margin:8px 0;"></div>
        <div id="print-total" style="text-align:right; font-size:15px; font-weight:bold;"></div>
        <div id="print-info" style="font-size:10px; color:#666; margin-top:4px;"></div>
        <div style="border-top:1px dashed #000; margin:8px 0;"></div>
        <div style="text-align:center; font-size:11px;">¡Gracias por su compra!</div>
        <div style="text-align:center; font-size:10px; color:#666;">{{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const SLUG = '{{ $sucursal->slug }}';
let ventaActualId = null;

// ── Tabs ──────────────────────────────────────────────────────────────────
function cambiarTab(tab) {
    ['ticket','global','devolucion'].forEach(t => {
        const btn = document.getElementById('tab-' + t);
        btn.className = 'px-5 py-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700';
    });
    const active = document.getElementById('tab-' + tab);
    active.className = 'px-5 py-2 text-sm font-semibold border-b-2 border-blue-600 text-blue-700 bg-white';
}

// ── Filtros ───────────────────────────────────────────────────────────────
function filtrarVentas() {
    const estado  = document.getElementById('f-estado').value.toLowerCase();
    const metodo  = document.getElementById('f-metodo').value.toLowerCase();
    const buscar  = document.getElementById('f-buscar').value.toLowerCase();

    let total = 0, count = 0;
    document.querySelectorAll('.venta-row').forEach(row => {
        const eMatch = !estado || row.dataset.estado === estado;
        const mMatch = !metodo || row.dataset.metodo === metodo;
        const bMatch = !buscar || row.dataset.folio.includes(buscar);
        const show   = eMatch && mMatch && bMatch;
        row.style.display = show ? '' : 'none';
        if (show) {
            const t = parseFloat(row.querySelector('td:nth-child(6)').textContent.replace('Bs. ', '')) || 0;
            total += t; count++;
        }
    });
    document.getElementById('r-total').textContent  = 'Bs. ' + total.toFixed(2);
    document.getElementById('r-count').textContent  = count;
    document.getElementById('r-promedio').textContent = 'Bs. ' + (count > 0 ? (total/count).toFixed(2) : '0.00');
}

// ── Ver detalle ───────────────────────────────────────────────────────────
async function verDetalle(id, folio) {
    ventaActualId = id;
    document.getElementById('modal-detalle-titulo').innerHTML =
        `<i class="fas fa-receipt mr-2 text-blue-600"></i>Detalle: ${folio}`;
    document.getElementById('d-items').innerHTML =
        '<tr><td colspan="5" class="text-center py-4 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando...</td></tr>';
    document.getElementById('modal-detalle').classList.remove('hidden');

    try {
        const res  = await fetch(`/${SLUG}/ventas/${id}/detalle`);
        const data = await res.json();
        if (data.error) { alert(data.error); cerrarDetalle(); return; }

        const v = data.venta;
        document.getElementById('d-folio').textContent  = v.folio;
        document.getElementById('d-fecha').textContent  = new Date(v.fecha_venta).toLocaleString('es-BO');
        document.getElementById('d-metodo').textContent = v.metodo_pago;
        document.getElementById('d-subtotal').textContent = 'Bs. ' + parseFloat(v.subtotal||0).toFixed(2);
        document.getElementById('d-iva').textContent    = 'Bs. ' + parseFloat(v.iva||0).toFixed(2);
        document.getElementById('d-total').textContent  = 'Bs. ' + parseFloat(v.total).toFixed(2);

        const rows = data.items.map(i => `
            <tr class="border-b">
                <td class="p-2 font-mono text-xs text-gray-400">${i.codigo_barras||'-'}</td>
                <td class="p-2">${i.nombre}</td>
                <td class="p-2 text-center">${i.cantidad}</td>
                <td class="p-2 text-right">Bs. ${parseFloat(i.precio_unitario).toFixed(2)}</td>
                <td class="p-2 text-right font-bold">Bs. ${parseFloat(i.subtotal).toFixed(2)}</td>
            </tr>`).join('');
        document.getElementById('d-items').innerHTML = rows || '<tr><td colspan="5" class="text-center py-4 text-gray-400">Sin ítems</td></tr>';

        // Configurar botón cancelar
        const btnCancelar = document.getElementById('btn-cancelar-venta');
        if (v.estado === 'cancelada') {
            btnCancelar.disabled = true;
            btnCancelar.textContent = 'Ya cancelada';
            btnCancelar.className = 'btn-secondary flex-1 opacity-50 cursor-not-allowed';
        } else {
            btnCancelar.disabled = false;
            btnCancelar.innerHTML = '<i class="fas fa-ban mr-2"></i>Cancelar Venta';
            btnCancelar.className = 'btn-danger flex-1';
            btnCancelar.onclick = () => cancelarVenta(id, v);
        }

        // Preparar área de impresión
        const printItems = data.items.map(i =>
            `<div style="display:flex;justify-content:space-between;padding:2px 0;">
                <span>${i.nombre} x${i.cantidad}</span>
                <span>Bs.${parseFloat(i.subtotal).toFixed(2)}</span>
            </div>`).join('');
        document.getElementById('print-items').innerHTML = printItems;
        document.getElementById('print-total').textContent = 'TOTAL: Bs. ' + parseFloat(v.total).toFixed(2);
        document.getElementById('print-info').textContent = `Folio: ${v.folio} | ${v.metodo_pago}`;

    } catch(e) {
        alert('Error al cargar el detalle');
        cerrarDetalle();
    }
}

function cerrarDetalle() {
    document.getElementById('modal-detalle').classList.add('hidden');
    ventaActualId = null;
}

async function cancelarVenta(id, venta) {
    if (!confirm(`¿Cancelar la venta ${venta.folio}? Se restaurará el stock.`)) return;
    const res = await fetch(`/${SLUG}/ventas/${id}/cancelar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    });
    const data = await res.json();
    if (data.success) { alert('Venta cancelada correctamente'); location.reload(); }
    else { alert(data.error || 'Error al cancelar'); }
}

function imprimirDetalle() {
    document.getElementById('print-area').style.display = 'block';
    window.print();
    setTimeout(() => { document.getElementById('print-area').style.display = 'none'; }, 1000);
}

function exportarExcel() {
    const rows = [['Fecha','Folio','Método','Subtotal','IVA','Total','Estado']];
    document.querySelectorAll('.venta-row:not([style*="none"])').forEach(row => {
        const cells = row.querySelectorAll('td');
        rows.push([cells[0].textContent.trim(), cells[1].textContent.trim(),
            cells[2].textContent.trim(), cells[3].textContent.trim(),
            cells[4].textContent.trim(), cells[5].textContent.trim(), cells[6].textContent.trim()]);
    });
    let csv = rows.map(r => r.map(c => '"'+c.replace(/"/g,'""')+'"').join(',')).join('\n');
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,\uFEFF' + encodeURIComponent(csv);
    a.download = 'ventas_{{ $sucursal->slug }}_{{ now()->format("Ymd") }}.csv';
    a.click();
}
</script>
@endsection
