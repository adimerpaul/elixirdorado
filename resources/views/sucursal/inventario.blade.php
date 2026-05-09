@extends('layouts.sucursal')
@section('titulo', 'Inventario')

@section('extra-styles')
.tab-inv { padding: 7px 16px; font-size: 13px; font-weight: 500; cursor: pointer;
    border: 1px solid #cbd5e1; border-radius: 4px;
    background: linear-gradient(180deg,#f8fafc,#e2e8f0); color: #374151; }
.tab-inv:hover { background: linear-gradient(180deg,#e3eaf3,#ccd8e6); }
.tab-inv.active { background: linear-gradient(180deg,#2563eb,#1d4ed8); color:white; border-color:#1d4ed8; }
.stock-bar { height: 6px; border-radius: 3px; background: #e2e8f0; overflow: hidden; }
.stock-bar-fill { height: 100%; border-radius: 3px; }
@endsection

@section('content')
{{-- Barra de sección --}}
<div class="section-bar flex items-center justify-between flex-wrap gap-2">
    <span class="font-bold text-blue-900 text-lg">F4 - INVENTARIO</span>
    <div class="flex gap-2 flex-wrap">
        <button onclick="cambiarTab('bajo')"    id="t-bajo"    class="tab-inv active">
            <i class="fas fa-exclamation-triangle mr-1 text-red-400"></i>Productos bajos en Inventario
        </button>
        <button onclick="cambiarTab('todos')"   id="t-todos"   class="tab-inv">
            <i class="fas fa-list mr-1"></i>Reporte de Inventario
        </button>
        <button onclick="cambiarTab('agregar')" id="t-agregar" class="tab-inv">
            <i class="fas fa-plus mr-1 text-green-500"></i>Agregar
        </button>
        <button onclick="cambiarTab('ajustes')" id="t-ajustes" class="tab-inv">
            <i class="fas fa-edit mr-1 text-orange-400"></i>Ajustes
        </button>
        <button onclick="cambiarTab('movs')"    id="t-movs"    class="tab-inv">
            <i class="fas fa-exchange-alt mr-1 text-blue-400"></i>Reporte de Movimientos
        </button>
    </div>
    <a href="/{{ $sucursal->slug }}/inventario/excel" class="btn-secondary text-sm inline-flex items-center">
        <i class="fas fa-file-excel mr-1 text-green-600"></i>Exportar a Excel
    </a>
</div>

<div class="p-4" style="padding-bottom:60px;">

    {{-- ══ TAB: PRODUCTOS BAJOS ══════════════════════════════════════ --}}
    <div id="tab-bajo">
        <div class="mb-3">
            <h2 class="text-lg font-bold text-gray-800">PRODUCTOS BAJOS EN INVENTARIO</h2>
            <p class="text-sm text-gray-500">Productos con existencia igual o menor a su inventario mínimo.</p>
        </div>
        @php $bajos = $productos->filter(fn($p) => $p->stock_actual <= ($p->stock_minimo ?? 5)); @endphp
        @if($bajos->isEmpty())
        <div class="card p-12 text-center text-gray-400">
            <i class="fas fa-check-circle text-5xl text-green-400 mb-3 block"></i>
            <h3 class="text-xl font-bold text-green-600 mb-1">¡Todo en orden!</h3>
            <p>No hay productos con bajo inventario en este momento.</p>
        </div>
        @else
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="table-header">
                    <tr>
                        <th class="text-left p-3">Código</th>
                        <th class="text-left p-3">Descripción del Producto</th>
                        <th class="text-right p-3">Precio Venta</th>
                        <th class="text-left p-3">Departamento</th>
                        <th class="text-center p-3">Existencia</th>
                        <th class="text-center p-3">Inv. Mínimo</th>
                        <th class="text-center p-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bajos as $p)
                    <tr class="table-row border-b">
                        <td class="p-3 font-mono text-xs text-gray-400">{{ $p->codigo_barras ?? '-' }}</td>
                        <td class="p-3 font-medium text-gray-800">{{ $p->nombre }}</td>
                        <td class="p-3 text-right text-green-700 font-bold">Bs. {{ number_format($p->precio_venta, 2) }}</td>
                        <td class="p-3 text-gray-600">{{ $categorias->firstWhere('id',$p->categoria_id)->nombre ?? '- Sin Departamento -' }}</td>
                        <td class="p-3 text-center">
                            <span class="{{ $p->stock_actual <= 0 ? 'badge-red' : 'badge-yellow' }} text-sm font-bold">
                                {{ $p->stock_actual }}
                            </span>
                        </td>
                        <td class="p-3 text-center text-gray-600">{{ $p->stock_minimo ?? 5 }}</td>
                        <td class="p-3 text-center">
                            <button onclick="abrirAjuste({{ json_encode($p) }})" class="btn-primary px-3 py-1 text-xs">
                                <i class="fas fa-plus mr-1"></i>Ajustar
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ══ TAB: TODOS LOS PRODUCTOS ══════════════════════════════════ --}}
    <div id="tab-todos" class="hidden">
        <div class="card p-3 mb-3 flex gap-3 items-center">
            <input type="text" id="buscar-inv" placeholder="Buscar producto..." class="input-field flex-1" oninput="debounceInv()">
            <select id="cat-inv" class="input-field" style="width:180px;" onchange="filtrarInv()">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $c)
                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                @endforeach
            </select>
            <select id="estado-inv" class="input-field" style="width:160px;" onchange="filtrarInv()">
                <option value="">Todos los estados</option>
                <option value="ok">Stock normal</option>
                <option value="bajo">Stock bajo</option>
                <option value="sin">Sin stock</option>
            </select>
        </div>
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="table-header">
                    <tr>
                        <th class="text-left p-3">Código</th>
                        <th class="text-left p-3">Nombre</th>
                        <th class="text-left p-3">Categoría</th>
                        <th class="text-right p-3">P. Compra</th>
                        <th class="text-right p-3">P. Venta</th>
                        <th class="text-center p-3">Stock</th>
                        <th class="text-center p-3">Mín.</th>
                        <th class="p-3" style="width:100px;">Nivel</th>
                        <th class="text-center p-3">Estado</th>
                        <th class="text-center p-3">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-todos">
                    @foreach($productos as $p)
                    @php
                        $min   = $p->stock_minimo ?? 5;
                        // Estimación de máximo basada en el mínimo (3x). El campo stock_maximo no existe en la BD.
                        $max   = $min * 3;
                        $pct   = $max > 0 ? min(100, round($p->stock_actual / $max * 100)) : 0;
                        $color = $p->stock_actual <= 0 ? '#ef4444' : ($p->stock_actual <= $min ? '#f59e0b' : '#22c55e');
                    @endphp
                    <tr class="table-row border-b inv-row"
                        data-nombre="{{ strtolower($p->nombre) }}"
                        data-cat="{{ $p->categoria_id }}"
                        data-estado="{{ $p->stock_actual <= 0 ? 'sin' : ($p->stock_actual <= $min ? 'bajo' : 'ok') }}">
                        <td class="p-3 font-mono text-xs text-gray-400">{{ $p->codigo_barras ?? '-' }}</td>
                        <td class="p-3 font-medium text-gray-800">{{ $p->nombre }}</td>
                        <td class="p-3 text-gray-500 text-xs">{{ $categorias->firstWhere('id',$p->categoria_id)->nombre ?? '-' }}</td>
                        <td class="p-3 text-right text-gray-500">Bs. {{ number_format($p->precio_compra ?? 0, 2) }}</td>
                        <td class="p-3 text-right font-bold text-green-700">Bs. {{ number_format($p->precio_venta, 2) }}</td>
                        <td class="p-3 text-center font-bold text-lg" style="color:{{ $color }}">{{ $p->stock_actual }}</td>
                        <td class="p-3 text-center text-gray-500 text-xs">{{ $min }}</td>
                        <td class="p-3">
                            <div class="stock-bar"><div class="stock-bar-fill" style="width:{{ $pct }}%;background:{{ $color }}"></div></div>
                            <div class="text-xs text-gray-400 mt-1 text-center">{{ $pct }}%</div>
                        </td>
                        <td class="p-3 text-center">
                            @if($p->stock_actual <= 0)
                                <span class="badge-red">Sin stock</span>
                            @elseif($p->stock_actual <= $min)
                                <span class="badge-yellow">Bajo</span>
                            @else
                                <span class="badge-green">Normal</span>
                            @endif
                        </td>
                        <td class="p-3 text-center">
                            <button onclick="abrirAjuste({{ json_encode($p) }})" class="text-blue-500 hover:text-blue-700 mr-2" title="Ajustar stock">
                                <i class="fas fa-boxes"></i>
                            </button>
                            <button onclick="editarProducto({{ json_encode($p) }})" class="text-orange-500 hover:text-orange-700" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-3 text-sm text-gray-500 border-t bg-gray-50 flex justify-between">
                <span>{{ $productos->count() }} productos en total</span>
                <span>
                    <span class="badge-green mr-2">Normal: {{ $productos->filter(fn($p) => $p->stock_actual > ($p->stock_minimo ?? 5))->count() }}</span>
                    <span class="badge-yellow mr-2">Bajo: {{ $productos->filter(fn($p) => $p->stock_actual > 0 && $p->stock_actual <= ($p->stock_minimo ?? 5))->count() }}</span>
                    <span class="badge-red">Sin stock: {{ $productos->filter(fn($p) => $p->stock_actual <= 0)->count() }}</span>
                </span>
            </div>
        </div>
    </div>

    {{-- ══ TAB: AGREGAR PRODUCTO ══════════════════════════════════════ --}}
    <div id="tab-agregar" class="hidden">
        <div class="card p-6 max-w-2xl">
            <h2 class="text-xl font-bold mb-4"><i class="fas fa-plus-circle mr-2 text-green-600"></i>Agregar Nuevo Producto</h2>
            <form method="POST" action="/{{ $sucursal->slug }}/productos">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-700 block mb-1">Nombre del Producto *</label>
                        <input type="text" name="nombre" class="input-field" required placeholder="Ej: Ron Bacardí 750ml">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">Código de Barras</label>
                        <input type="text" name="codigo_barras" class="input-field" placeholder="Escanear o escribir">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">Categoría / Departamento</label>
                        <select name="categoria_id" class="input-field">
                            <option value="">Sin categoría</option>
                            @foreach($categorias as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">Precio de Compra (Bs.) *</label>
                        <input type="number" name="precio_compra" class="input-field" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">Precio de Venta (Bs.) *</label>
                        <input type="number" name="precio_venta" class="input-field" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">Stock Actual *</label>
                        <input type="number" name="stock_actual" class="input-field" min="0" required placeholder="0">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">Stock Mínimo *</label>
                        <input type="number" name="stock_minimo" class="input-field" min="0" required placeholder="5">
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button type="button" onclick="cambiarTab('bajo')" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="btn-success flex-1 py-3">
                        <i class="fas fa-save mr-2"></i>Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ══ TAB: AJUSTES ══════════════════════════════════════════════ --}}
    <div id="tab-ajustes" class="hidden">
        <div class="card p-6 max-w-2xl">
            <h2 class="text-xl font-bold mb-4"><i class="fas fa-edit mr-2 text-orange-500"></i>Ajuste de Inventario</h2>
            <p class="text-sm text-gray-600 mb-4">Corrige el stock de un producto por diferencia de inventario, merma o error.</p>
            <form method="POST" action="/{{ $sucursal->slug }}/inventario/ajuste">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-700 block mb-1">Producto</label>
                        <select name="producto_id" class="input-field" required id="sel-ajuste" onchange="verStockActual()">
                            <option value="">Seleccionar producto...</option>
                            @foreach($productos as $p)
                            <option value="{{ $p->id }}" data-stock="{{ $p->stock_actual }}">{{ $p->nombre }} (Stock: {{ $p->stock_actual }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">Tipo de Ajuste</label>
                        <select name="tipo_ajuste" class="input-field" required>
                            <option value="entrada">Entrada (agregar stock)</option>
                            <option value="salida">Salida (restar stock)</option>
                            <option value="correccion">Corrección (stock exacto)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-1">Cantidad</label>
                        <input type="number" name="cantidad" class="input-field" min="1" required placeholder="0">
                    </div>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-700 block mb-1">Motivo del ajuste</label>
                        <input type="text" name="motivo" class="input-field" placeholder="Ej: inventario físico, merma, compra emergente...">
                    </div>
                </div>
                <div id="stock-preview" class="mt-3 p-3 bg-blue-50 rounded text-sm text-blue-800 hidden">
                    Stock actual: <strong id="stock-act">-</strong>
                </div>
                <div class="flex gap-3 mt-5">
                    <button type="button" onclick="cambiarTab('bajo')" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1 py-3">
                        <i class="fas fa-check mr-2"></i>Aplicar Ajuste
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ══ TAB: MOVIMIENTOS ══════════════════════════════════════════ --}}
    <div id="tab-movs" class="hidden">
        <div class="card p-8 text-center text-gray-400">
            <i class="fas fa-exchange-alt text-5xl mb-3 block opacity-30"></i>
            <h3 class="text-xl font-bold text-gray-600 mb-2">Reporte de Movimientos</h3>
            <p class="text-sm">El kardex de movimientos de inventario estará disponible próximamente.</p>
            <p class="text-sm mt-1">Por ahora, todas las entradas y salidas se registran en las ventas.</p>
            <a href="/{{ $sucursal->slug }}/ventas" class="btn-primary inline-block mt-4 px-6">
                <i class="fas fa-receipt mr-1"></i>Ver Historial de Ventas
            </a>
        </div>
    </div>

</div>

{{-- MODAL AJUSTE RÁPIDO --}}
<div id="modal-ajuste" class="modal-overlay hidden">
    <div class="modal-box" style="max-width:440px;">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold"><i class="fas fa-boxes mr-2 text-blue-600"></i>Ajuste de Stock</h3>
            <button onclick="cerrarAjuste()" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="mb-3 bg-gray-50 p-3 rounded">
            <div class="text-sm text-gray-500">Producto:</div>
            <div class="font-bold text-gray-800" id="ajuste-nombre">-</div>
            <div class="text-sm mt-1">Stock actual: <strong class="text-blue-700" id="ajuste-stock">-</strong></div>
        </div>
        <form method="POST" action="/{{ $sucursal->slug }}/inventario/ajuste">
            @csrf
            <input type="hidden" name="producto_id" id="ajuste-id">
            <div class="mb-3">
                <label class="text-sm font-medium text-gray-700 block mb-1">Tipo de Ajuste</label>
                <select name="tipo_ajuste" class="input-field">
                    <option value="entrada">✅ Entrada (agregar stock)</option>
                    <option value="salida">❌ Salida (restar stock)</option>
                    <option value="correccion">🔄 Corrección (stock exacto)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="text-sm font-medium text-gray-700 block mb-1">Cantidad</label>
                <input type="number" name="cantidad" class="input-field text-xl font-bold" min="1" required placeholder="0">
            </div>
            <div class="mb-4">
                <label class="text-sm font-medium text-gray-700 block mb-1">Motivo (opcional)</label>
                <input type="text" name="motivo" class="input-field" placeholder="Compra, merma, corrección...">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="cerrarAjuste()" class="btn-secondary flex-1">Cancelar</button>
                <button type="submit" class="btn-primary flex-1 py-3">
                    <i class="fas fa-check mr-2"></i>Aplicar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDITAR PRODUCTO --}}
<div id="modal-editar" class="modal-overlay hidden">
    <div class="modal-box" style="min-width:520px;">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold"><i class="fas fa-edit mr-2 text-orange-500"></i>Editar Producto</h3>
            <button onclick="cerrarEditar()" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="/{{ $sucursal->slug }}/productos/editar" id="form-editar">
            @csrf @method('PUT')
            <input type="hidden" name="id" id="e-id">
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="text-sm font-medium text-gray-700 block mb-1">Nombre *</label>
                    <input type="text" name="nombre" id="e-nombre" class="input-field" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Código Barras</label>
                    <input type="text" name="codigo_barras" id="e-codigo" class="input-field">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Categoría</label>
                    <select name="categoria_id" id="e-cat" class="input-field">
                        <option value="">Sin categoría</option>
                        @foreach($categorias as $c)
                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Precio Compra</label>
                    <input type="number" name="precio_compra" id="e-compra" class="input-field" step="0.01" min="0">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Precio Venta *</label>
                    <input type="number" name="precio_venta" id="e-venta" class="input-field" step="0.01" min="0" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Stock Mínimo</label>
                    <input type="number" name="stock_minimo" id="e-min" class="input-field" min="0">
                </div>
            </div>
            <div class="flex gap-3 mt-4">
                <button type="button" onclick="cerrarEditar()" class="btn-secondary flex-1">Cancelar</button>
                <button type="submit" class="btn-primary flex-1 py-3">
                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ── Tabs ──────────────────────────────────────────────────────────────
const TABS = ['bajo','todos','agregar','ajustes','movs'];
function cambiarTab(id) {
    TABS.forEach(t => {
        document.getElementById('tab-' + t)?.classList.add('hidden');
        document.getElementById('t-' + t)?.classList.remove('active');
    });
    document.getElementById('tab-' + id)?.classList.remove('hidden');
    document.getElementById('t-' + id)?.classList.add('active');
}

// ── Filtrar inventario ────────────────────────────────────────────────
function filtrarInv() {
    const q      = document.getElementById('buscar-inv').value.toLowerCase();
    const cat    = document.getElementById('cat-inv').value;
    const estado = document.getElementById('estado-inv').value;
    document.querySelectorAll('.inv-row').forEach(row => {
        const nOk = !q      || row.dataset.nombre.includes(q);
        const cOk = !cat    || row.dataset.cat == cat;
        const eOk = !estado || row.dataset.estado === estado;
        row.style.display = (nOk && cOk && eOk) ? '' : 'none';
    });
}

// Debounce de 250 ms — alivia CPU en PCs antiguas con catálogos grandes
let _invTimer = null;
function debounceInv() {
    clearTimeout(_invTimer);
    _invTimer = setTimeout(filtrarInv, 250);
}

// ── Modal ajuste rápido ───────────────────────────────────────────────
function abrirAjuste(prod) {
    document.getElementById('ajuste-id').value    = prod.id;
    document.getElementById('ajuste-nombre').textContent = prod.nombre;
    document.getElementById('ajuste-stock').textContent  = prod.stock_actual;
    document.getElementById('modal-ajuste').classList.remove('hidden');
}
function cerrarAjuste() { document.getElementById('modal-ajuste').classList.add('hidden'); }

// ── Modal editar producto ─────────────────────────────────────────────
function editarProducto(prod) {
    document.getElementById('e-id').value     = prod.id;
    document.getElementById('e-nombre').value = prod.nombre || '';
    document.getElementById('e-codigo').value = prod.codigo_barras || '';
    document.getElementById('e-compra').value = prod.precio_compra || '';
    document.getElementById('e-venta').value  = prod.precio_venta || '';
    document.getElementById('e-min').value    = prod.stock_minimo || '';
    document.getElementById('e-cat').value    = prod.categoria_id || '';
    document.getElementById('modal-editar').classList.remove('hidden');
}
function cerrarEditar() { document.getElementById('modal-editar').classList.add('hidden'); }

// ── Selector de stock en pestaña ajustes ─────────────────────────────
function verStockActual() {
    const sel = document.getElementById('sel-ajuste');
    const opt = sel.options[sel.selectedIndex];
    if (opt.dataset.stock !== undefined) {
        document.getElementById('stock-act').textContent = opt.dataset.stock;
        document.getElementById('stock-preview').classList.remove('hidden');
    }
}

// ── Exportar CSV ──────────────────────────────────────────────────────
function exportarCSV() {
    const rows = [['Código','Nombre','Precio Venta','Stock Actual','Stock Mínimo','Estado']];
    document.querySelectorAll('.inv-row').forEach(row => {
        const cells = row.querySelectorAll('td');
        rows.push([cells[0].textContent.trim(), cells[1].textContent.trim(),
            cells[4].textContent.trim(), cells[5].textContent.trim(),
            cells[6].textContent.trim(), cells[8].textContent.trim()]);
    });
    const csv = rows.map(r => r.map(c => '"'+c.replace(/"/g,'""')+'"').join(',')).join('\n');
    const a = document.createElement('a'); a.href = 'data:text/csv;charset=utf-8,\uFEFF' + encodeURIComponent(csv);
    a.download = 'inventario_{{ $sucursal->slug }}.csv'; a.click();
}
</script>
@endsection
