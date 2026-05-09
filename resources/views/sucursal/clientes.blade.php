@extends('layouts.sucursal')
@section('titulo', 'Clientes')

@section('extra-styles')
.modal-overlay.active { display: flex !important; }
@endsection

@section('content')
<div class="section-bar flex items-center justify-between">
    <span class="font-bold text-blue-900 text-lg">CLIENTES</span>
    <button onclick="abrirModal()" class="btn-success text-sm">
        <i class="fas fa-user-plus mr-1"></i>+ Nuevo Cliente
    </button>
</div>

<div class="p-4" style="padding-bottom:60px;">

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-800 px-4 py-3 rounded mb-4 flex justify-between">
        <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
        <button onclick="this.parentElement.remove()" class="text-green-600"><i class="fas fa-times"></i></button>
    </div>
    @endif

    {{-- Barra de búsqueda --}}
    <div class="card p-3 mb-4 flex gap-3">
        <div class="relative flex-1">
            <input type="text" id="buscar-cliente" placeholder="Buscar por nombre, teléfono o email..."
                class="input-field pr-10" oninput="filtrarClientes()">
            <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
        </div>
        <button onclick="abrirModal()" class="btn-primary">
            <i class="fas fa-user-plus mr-1"></i>Nuevo Cliente
        </button>
    </div>

    {{-- Tabla de clientes --}}
    <div class="card overflow-hidden">
        @if($clientes->count() > 0)
        <table class="w-full text-sm">
            <thead class="table-header">
                <tr>
                    <th class="text-left p-3">Nombre</th>
                    <th class="text-left p-3">Teléfono</th>
                    <th class="text-left p-3">Email</th>
                    <th class="text-left p-3">RFC/NIT</th>
                    <th class="text-left p-3">Dirección</th>
                    <th class="text-right p-3">Límite Crédito</th>
                    <th class="text-center p-3">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-clientes">
                @foreach($clientes as $c)
                <tr class="table-row border-b cliente-row"
                    data-nombre="{{ strtolower($c->nombre) }}"
                    data-tel="{{ $c->telefono ?? '' }}"
                    data-email="{{ strtolower($c->email ?? '') }}">
                    <td class="p-3 font-semibold text-gray-800">
                        <i class="fas fa-user-circle text-blue-300 mr-2"></i>{{ $c->nombre }}
                    </td>
                    <td class="p-3 text-gray-600">{{ $c->telefono ?? '-' }}</td>
                    <td class="p-3 text-gray-600 text-xs">{{ $c->email ?? '-' }}</td>
                    <td class="p-3 text-gray-500 font-mono text-xs">{{ $c->rfc_nit ?? '-' }}</td>
                    <td class="p-3 text-gray-500 text-xs">{{ Str::limit($c->direccion ?? '-', 30) }}</td>
                    <td class="p-3 text-right font-bold {{ ($c->limite_credito ?? 0) > 0 ? 'text-green-600' : 'text-gray-400' }}">
                        Bs. {{ number_format($c->limite_credito ?? 0, 2) }}
                    </td>
                    <td class="p-3 text-center">
                        <button onclick="editarCliente({{ json_encode($c) }})"
                            class="text-blue-500 hover:text-blue-700 mr-2" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" action="/{{ $sucursal->slug }}/clientes/{{ $c->id }}"
                            class="inline" onsubmit="return confirm('¿Eliminar a {{ addslashes($c->nombre) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-600" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3 text-sm text-gray-500 border-t bg-gray-50">
            {{ $clientes->count() }} cliente(s) registrado(s)
        </div>
        @else
        <div class="text-center py-16 text-gray-400">
            <i class="fas fa-users text-5xl mb-4 block opacity-30"></i>
            <h3 class="text-xl font-bold text-gray-600 mb-2">Sin clientes registrados</h3>
            <p class="text-sm mb-4">Agrega tu primer cliente para gestionar créditos e historial.</p>
            <button onclick="abrirModal()" class="btn-primary px-6 py-2">
                <i class="fas fa-user-plus mr-2"></i>Agregar Primer Cliente
            </button>
        </div>
        @endif
    </div>
</div>

{{-- ══ MODAL NUEVO / EDITAR CLIENTE ══════════════════════════════════ --}}
<div id="modal-cliente" class="modal-overlay hidden" style="display:none;">
    <div class="modal-box" style="min-width:480px;" onclick="event.stopPropagation()">
        {{-- Header --}}
        <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800" id="modal-titulo">
                <i class="fas fa-user-plus mr-2 text-blue-600"></i>Nuevo Cliente
            </h3>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-700 text-xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Formulario — POST normal, SIN JavaScript que lo intercepte --}}
        <form method="POST" id="form-cliente" action="/{{ $sucursal->slug }}/clientes">
            @csrf
            {{-- Para edición, se cambia a PUT vía JS --}}
            <input type="hidden" name="_method" id="form-method" value="POST">
            <input type="hidden" name="cliente_id" id="campo-id" value="">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Nombre *</label>
                    <input type="text" name="nombre" id="c-nombre" class="input-field" required
                        placeholder="Nombre completo del cliente">
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Teléfono</label>
                    <input type="tel" name="telefono" id="c-telefono" class="input-field"
                        placeholder="76000000">
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Email</label>
                    <input type="email" name="email" id="c-email" class="input-field"
                        placeholder="cliente@email.com">
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">RFC / NIT</label>
                    <input type="text" name="rfc_nit" id="c-nit" class="input-field"
                        placeholder="NIT o número de documento">
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Límite de Crédito (Bs.)</label>
                    <input type="number" name="limite_credito" id="c-credito" class="input-field"
                        step="0.01" min="0" placeholder="0.00" value="0">
                </div>
                <div class="col-span-2">
                    <label class="text-sm font-semibold text-gray-700 block mb-1">Dirección</label>
                    <textarea name="direccion" id="c-direccion" class="input-field" rows="2"
                        placeholder="Av. o calle, ciudad..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-5 pt-4 border-t border-gray-200">
                <button type="button" onclick="cerrarModal()" class="btn-secondary flex-1">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <button type="submit" class="btn-primary flex-1 py-3">
                    <i class="fas fa-save mr-1"></i>Guardar Cliente
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const modalEl = document.getElementById('modal-cliente');

function abrirModal() {
    // Resetear a modo "nuevo"
    document.getElementById('modal-titulo').innerHTML = '<i class="fas fa-user-plus mr-2 text-blue-600"></i>Nuevo Cliente';
    document.getElementById('form-method').value = 'POST';
    document.getElementById('campo-id').value = '';
    document.getElementById('form-cliente').reset();
    // Resetear action al endpoint de creación
    document.getElementById('form-cliente').action = '/{{ $sucursal->slug }}/clientes';
    // Mostrar modal
    modalEl.style.display = 'flex';
    setTimeout(() => document.getElementById('c-nombre').focus(), 100);
}

function cerrarModal() {
    modalEl.style.display = 'none';
}

// Cerrar al hacer click en el fondo
modalEl.addEventListener('click', function(e) {
    if (e.target === modalEl) cerrarModal();
});

// Cerrar con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarModal();
});

function editarCliente(c) {
    document.getElementById('modal-titulo').innerHTML = '<i class="fas fa-edit mr-2 text-orange-500"></i>Editar Cliente';
    document.getElementById('form-method').value = 'PUT';
    document.getElementById('campo-id').value = c.id;
    document.getElementById('c-nombre').value   = c.nombre || '';
    document.getElementById('c-telefono').value = c.telefono || '';
    document.getElementById('c-email').value    = c.email || '';
    document.getElementById('c-nit').value      = c.rfc_nit || '';
    document.getElementById('c-credito').value  = c.limite_credito || 0;
    document.getElementById('c-direccion').value = c.direccion || '';
    // Cambiar action al endpoint de edición
    document.getElementById('form-cliente').action = '/{{ $sucursal->slug }}/clientes/' + c.id;
    modalEl.style.display = 'flex';
}

function filtrarClientes() {
    const q = document.getElementById('buscar-cliente').value.toLowerCase();
    document.querySelectorAll('.cliente-row').forEach(row => {
        const match = row.dataset.nombre.includes(q)
                   || row.dataset.tel.includes(q)
                   || row.dataset.email.includes(q);
        row.style.display = match ? '' : 'none';
    });
}
</script>
@endsection
