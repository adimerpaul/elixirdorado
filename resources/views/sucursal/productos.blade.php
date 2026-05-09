@extends('layouts.sucursal')
@section('titulo', 'Productos')
@section('content')
<div class="section-bar flex items-center justify-between">
    <span class="font-bold text-blue-900 text-lg">F3 - PRODUCTOS</span>
    <button onclick="abrirModalNuevo()" class="btn-primary text-sm">
        <i class="fas fa-plus mr-1"></i> Agregar Producto
    </button>
</div>

<div class="p-4" style="padding-bottom:60px;">
    <!-- Barra de búsqueda -->
    <div class="card p-3 mb-4">
        <div class="flex gap-3 items-center flex-wrap">
            <div class="relative flex-1" style="min-width:200px;">
                <input type="text" id="buscar-prod" placeholder="Buscar por nombre, código..."
                    class="input-field pr-10" oninput="filtrarProductos()">
                <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
            </div>
            <select id="filtro-cat" class="input-field" style="width:180px;" onchange="filtrarProductos()">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $cat)
                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                @endforeach
            </select>
            <select id="filtro-estado" class="input-field" style="width:140px;" onchange="filtrarProductos()">
                <option value="">Todos</option>
                <option value="1">Solo activos</option>
                <option value="0">Solo inactivos</option>
            </select>
            <a href="/{{ $sucursal->slug }}/inventario/excel" class="btn-secondary text-sm">
                <i class="fas fa-file-excel mr-1 text-green-600"></i>Exportar Excel
            </a>
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="table-header">
                <tr>
                    <th class="text-center p-3">Imagen</th>
                    <th class="text-left p-3">Código</th>
                    <th class="text-left p-3">Nombre del Producto</th>
                    <th class="text-left p-3">Categoría</th>
                    <th class="text-right p-3">Precio Compra</th>
                    <th class="text-right p-3">Precio Venta</th>
                    <th class="text-center p-3">Stock</th>
                    <th class="text-center p-3">Stock Mín.</th>
                    <th class="text-center p-3">Estado</th>
                    <th class="text-center p-3">Activo</th>
                    <th class="text-center p-3">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-productos">
                @forelse($productos as $prod)
                <tr class="table-row border-b {{ !$prod->activo ? 'opacity-50 bg-gray-50' : '' }}"
                    data-nombre="{{ strtolower($prod->nombre) }}"
                    data-cat="{{ $prod->categoria_id }}"
                    data-activo="{{ $prod->activo ? '1' : '0' }}">
                    <td class="p-3 text-center">
                        @if($prod->imagen)
                            <img src="{{ asset('storage/' . $prod->imagen) }}" alt="{{ $prod->nombre }}"
                                 class="rounded object-cover mx-auto" style="width:48px;height:48px;">
                        @else
                            <span class="text-gray-300 text-2xl">📷</span>
                        @endif
                    </td>
                    <td class="p-3 font-mono text-xs text-gray-500">{{ $prod->codigo_barras ?? '-' }}</td>
                    <td class="p-3 font-medium {{ $prod->activo ? 'text-gray-800' : 'text-gray-400 line-through' }}">
                        {{ $prod->nombre }}
                    </td>
                    <td class="p-3 text-gray-600">
                        {{ $categorias->firstWhere('id', $prod->categoria_id)->nombre ?? 'Sin categoría' }}
                    </td>
                    <td class="p-3 text-right text-gray-700">Bs. {{ number_format($prod->precio_compra ?? 0, 2) }}</td>
                    <td class="p-3 text-right font-bold text-green-700">Bs. {{ number_format($prod->precio_venta, 2) }}</td>
                    <td class="p-3 text-center font-bold {{ $prod->stock_actual <= ($prod->stock_minimo ?? 5) ? 'text-red-600' : 'text-gray-800' }}">
                        {{ $prod->stock_actual }}
                    </td>
                    <td class="p-3 text-center text-gray-500">{{ $prod->stock_minimo ?? 5 }}</td>
                    <td class="p-3 text-center">
                        @if(!$prod->activo)
                            <span class="badge-red">Inactivo</span>
                        @elseif($prod->stock_actual <= 0)
                            <span class="badge-red">Sin stock</span>
                        @elseif($prod->stock_actual <= ($prod->stock_minimo ?? 5))
                            <span class="badge-yellow">Bajo</span>
                        @else
                            <span class="badge-green">Normal</span>
                        @endif
                    </td>
                    <td class="p-3 text-center">
                        <button onclick="toggleActivo({{ $prod->id }}, this)"
                            data-activo="{{ $prod->activo ? '1' : '0' }}"
                            title="{{ $prod->activo ? 'Deshabilitar producto' : 'Habilitar producto' }}"
                            class="toggle-btn {{ $prod->activo ? 'toggle-on' : 'toggle-off' }}">
                            <span class="toggle-circle"></span>
                        </button>
                    </td>
                    <td class="p-3 text-center whitespace-nowrap">
                        <button onclick="editarProducto({{ json_encode($prod) }})" class="text-blue-500 hover:text-blue-700 mr-2" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="eliminarProducto({{ $prod->id }})" class="text-red-400 hover:text-red-600" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-16 text-gray-400">
                        <i class="fas fa-box text-4xl mb-3 block opacity-30"></i>
                        No hay productos registrados. <a href="#" onclick="abrirModalNuevo()" class="text-blue-600 underline">Agregar el primero</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3 text-sm text-gray-500 border-t bg-gray-50 flex justify-between">
            <span>Total: {{ $productos->count() }} productos</span>
            <span>Activos: {{ $productos->where('activo', true)->count() }} &nbsp;|&nbsp; Inactivos: {{ $productos->where('activo', false)->count() }}</span>
        </div>
    </div>
</div>

<!-- MODAL AGREGAR/EDITAR PRODUCTO -->
<div id="modal-producto" class="modal-overlay hidden">
    <div class="modal-box" style="min-width:580px; max-height:90vh; overflow-y:auto;">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" id="modal-titulo"><i class="fas fa-box mr-2 text-blue-600"></i>Agregar Producto</h2>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="/{{ $sucursal->slug }}/productos" id="form-producto" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="form-method" value="POST">
            <input type="hidden" name="id" id="f-id" value="">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-sm font-medium text-gray-700 block mb-1">Nombre del Producto *</label>
                    <input type="text" name="nombre" id="f-nombre" class="input-field" required placeholder="Ej: Ron Bacardí 750ml">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Código de Barras</label>
                    <input type="text" name="codigo_barras" id="f-codigo" class="input-field" placeholder="Escanear o escribir">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Categoría</label>
                    <select name="categoria_id" id="f-cat" class="input-field">
                        <option value="">Sin categoría</option>
                        @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="text-sm font-medium text-gray-700 block mb-1">Descripción</label>
                    <input type="text" name="descripcion" id="f-desc" class="input-field" placeholder="Ej: Whisky escocés botella 750ml">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Precio de Compra *</label>
                    <input type="number" name="precio_compra" id="f-compra" class="input-field" step="0.01" min="0" required placeholder="0.00">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Precio de Venta *</label>
                    <input type="number" name="precio_venta" id="f-venta" class="input-field" step="0.01" min="0" required placeholder="0.00">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Stock Actual *</label>
                    <input type="number" name="stock_actual" id="f-stock" class="input-field" min="0" required placeholder="0">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Stock Mínimo *</label>
                    <input type="number" name="stock_minimo" id="f-min" class="input-field" min="0" required placeholder="5">
                </div>

                <!-- Imagen obligatoria al crear, opcional al editar -->
                <div class="col-span-2" id="imagen-wrap">
                    <label class="text-sm font-medium text-gray-700 block mb-1" id="imagen-label">
                        Imagen del Producto <span id="imagen-required-badge" class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-3 items-start">
                        <div id="img-preview-wrap" class="hidden flex-shrink-0">
                            <img id="img-preview" src="" alt="preview"
                                 class="rounded object-cover border" style="width:72px;height:72px;">
                        </div>
                        <div class="flex-1">
                            <input type="file" name="imagen" id="f-imagen" accept="image/*"
                                   class="input-field text-sm" onchange="previewImagen(this)">
                            <p class="text-xs text-gray-400 mt-1" id="imagen-hint">
                                <i class="fas fa-exclamation-circle text-red-400 mr-1"></i>
                                La imagen es <strong>obligatoria</strong> para nuevos productos. JPG, PNG o WEBP. Máx. 2MB.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="cerrarModal()" class="btn-secondary flex-1">Cancelar</button>
                <button type="submit" id="btn-guardar" class="btn-primary flex-1">
                    <i class="fas fa-save mr-2"></i>Guardar Producto
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('extra-styles')
/* Toggle switch */
.toggle-btn {
    position: relative; display: inline-flex; align-items: center;
    width: 44px; height: 24px; border-radius: 12px; border: none;
    cursor: pointer; transition: background 0.2s; padding: 0;
}
.toggle-on  { background: #22c55e; }
.toggle-off { background: #d1d5db; }
.toggle-circle {
    position: absolute; left: 3px; top: 3px;
    width: 18px; height: 18px; background: white;
    border-radius: 50%; transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.toggle-on .toggle-circle  { transform: translateX(20px); }
.toggle-off .toggle-circle { transform: translateX(0); }
@endsection

@section('scripts')
<script>
const SLUG = '{{ $sucursal->slug }}';

function filtrarProductos() {
    const q      = document.getElementById('buscar-prod').value.toLowerCase();
    const cat    = document.getElementById('filtro-cat').value;
    const estado = document.getElementById('filtro-estado').value;
    document.querySelectorAll('#tabla-productos tr[data-nombre]').forEach(row => {
        const nameMatch   = row.dataset.nombre.includes(q);
        const catMatch    = !cat || row.dataset.cat == cat;
        const estadoMatch = estado === '' || row.dataset.activo == estado;
        row.style.display = (nameMatch && catMatch && estadoMatch) ? '' : 'none';
    });
}

function abrirModalNuevo() {
    cerrarModal();
    // Imagen obligatoria para productos nuevos
    document.getElementById('f-imagen').required = true;
    document.getElementById('imagen-required-badge').style.display = '';
    document.getElementById('imagen-hint').innerHTML =
        '<i class="fas fa-exclamation-circle text-red-400 mr-1"></i>La imagen es <strong>obligatoria</strong> para nuevos productos. JPG, PNG o WEBP. Máx. 2MB.';
    document.getElementById('modal-producto').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modal-producto').classList.add('hidden');
    document.getElementById('form-producto').reset();
    document.getElementById('img-preview-wrap').classList.add('hidden');
    document.getElementById('img-preview').src = '';
    document.getElementById('f-id').value = '';
    document.getElementById('form-method').value = 'POST';
    document.getElementById('form-producto').action = '/' + SLUG + '/productos';
    document.getElementById('modal-titulo').innerHTML = '<i class="fas fa-box mr-2 text-blue-600"></i>Agregar Producto';
    document.getElementById('f-imagen').required = false;
}

function editarProducto(prod) {
    cerrarModal();
    document.getElementById('modal-titulo').innerHTML = '<i class="fas fa-edit mr-2 text-orange-500"></i>Editar Producto';
    document.getElementById('f-id').value      = prod.id || '';
    document.getElementById('f-nombre').value  = prod.nombre || '';
    document.getElementById('f-codigo').value  = prod.codigo_barras || '';
    document.getElementById('f-desc').value    = prod.descripcion || '';
    document.getElementById('f-compra').value  = prod.precio_compra || '';
    document.getElementById('f-venta').value   = prod.precio_venta || '';
    document.getElementById('f-stock').value   = prod.stock_actual || '';
    document.getElementById('f-min').value     = prod.stock_minimo || '';
    document.getElementById('f-cat').value     = prod.categoria_id || '';
    document.getElementById('form-method').value = 'PUT';
    document.getElementById('form-producto').action = '/' + SLUG + '/productos/editar';

    // Imagen opcional al editar
    document.getElementById('f-imagen').required = false;
    document.getElementById('imagen-required-badge').style.display = 'none';
    document.getElementById('imagen-hint').innerHTML =
        'Opcional: sube una nueva imagen para reemplazar la actual. JPG, PNG o WEBP. Máx. 2MB.';

    if (prod.imagen) {
        document.getElementById('img-preview').src = '/storage/' + prod.imagen;
        document.getElementById('img-preview-wrap').classList.remove('hidden');
    } else {
        document.getElementById('img-preview-wrap').classList.add('hidden');
    }

    document.getElementById('modal-producto').classList.remove('hidden');
}

function previewImagen(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('img-preview').src = e.target.result;
            document.getElementById('img-preview-wrap').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleActivo(id, btn) {
    const estaActivo = btn.dataset.activo === '1';
    const accion     = estaActivo ? 'deshabilitar' : 'habilitar';
    if (!confirm(`¿Deseas ${accion} este producto?`)) return;

    fetch('/' + SLUG + '/productos/' + id + '/toggle', {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        const nuevoActivo = data.activo ? '1' : '0';
        btn.dataset.activo = nuevoActivo;
        btn.className = 'toggle-btn ' + (data.activo ? 'toggle-on' : 'toggle-off');
        btn.title = data.activo ? 'Deshabilitar producto' : 'Habilitar producto';

        // Actualizar fila visual
        const row = btn.closest('tr');
        row.dataset.activo = nuevoActivo;
        if (data.activo) {
            row.classList.remove('opacity-50', 'bg-gray-50');
        } else {
            row.classList.add('opacity-50', 'bg-gray-50');
        }

        // Actualizar badge de estado
        const badge = row.querySelector('td:nth-child(9) span');
        if (badge && !data.activo) {
            badge.className = 'badge-red';
            badge.textContent = 'Inactivo';
        }

        // Actualizar nombre (tachado o no)
        const nombreTd = row.querySelector('td:nth-child(3)');
        if (nombreTd) {
            nombreTd.className = 'p-3 font-medium ' + (data.activo ? 'text-gray-800' : 'text-gray-400 line-through');
        }

        showToast(data.activo ? 'Producto habilitado' : 'Producto deshabilitado', data.activo ? 'success' : 'warning');
    })
    .catch(() => showToast('Error al cambiar el estado', 'error'));
}

function eliminarProducto(id) {
    if (!confirm('¿Eliminar este producto? Esta acción no se puede deshacer.')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/' + SLUG + '/productos/' + id;
    form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
