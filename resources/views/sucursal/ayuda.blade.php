@extends('layouts.sucursal')
@section('titulo', 'Ayuda')

@section('extra-styles')
.help-card { background: white; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.07); margin-bottom: 16px; overflow: hidden; }
.help-card-header { padding: 14px 20px; display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none; }
.help-card-header:hover { background: #f8fafc; }
.help-card-body { padding: 0 20px 18px 20px; display: none; }
.help-card-body.open { display: block; }
.shortcut-key { display: inline-flex; align-items: center; justify-content: center;
    background: #f1f5f9; border: 1px solid #cbd5e1; border-bottom: 3px solid #94a3b8;
    border-radius: 5px; padding: 3px 8px; font-family: monospace; font-size: 12px;
    font-weight: 700; color: #374151; min-width: 32px; }
.tip-box { background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 0 6px 6px 0; padding: 10px 14px; margin: 10px 0; font-size: 13px; color: #1e40af; }
.warn-box { background: #fef9c3; border-left: 4px solid #ca8a04; border-radius: 0 6px 6px 0; padding: 10px 14px; margin: 10px 0; font-size: 13px; color: #92400e; }
.step-num { width: 24px; height: 24px; background: #2563eb; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
.module-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.search-highlight { background: #fef08a; border-radius: 2px; }
@endsection

@section('content')
<div class="section-bar flex items-center justify-between">
    <span class="font-bold text-blue-900 text-lg"><i class="fas fa-question-circle mr-2 text-blue-600"></i>MANUAL DE USUARIO</span>
    <div class="flex gap-2">
        <input type="text" id="help-search" placeholder="Buscar en el manual..." class="input-field" style="width:260px;" oninput="buscarAyuda(this.value)">
        <button onclick="abrirTodo()" class="btn-secondary text-sm">Expandir todo</button>
    </div>
</div>

<div class="p-4" style="padding-bottom: 70px;">

    <!-- Banner de bienvenida -->
    <div style="background: linear-gradient(135deg, #1e3a5f, #2563eb); border-radius: 12px; padding: 24px 28px; margin-bottom: 24px; display: flex; align-items: center; gap: 20px;">
        <div class="text-5xl">🥃</div>
        <div>
            <h1 class="text-white text-xl font-black">Manual del Cajero — Elixirdorado POS</h1>
            <p class="text-blue-200 text-sm mt-1">Guía completa para operar el sistema. Puedes buscar cualquier función arriba.</p>
        </div>
        <div class="ml-auto text-right text-blue-300 text-xs">
            <div>Versión 1.0</div>
            <div class="mt-1">Sucursal: <strong class="text-white">{{ $sucursal->nombre }}</strong></div>
        </div>
    </div>

    <!-- Atajos de teclado rápidos -->
    <div class="help-card mb-6" style="border-left: 4px solid #2563eb;">
        <div class="help-card-header" onclick="toggleCard(this)">
            <div class="module-icon bg-blue-100"><i class="fas fa-keyboard text-blue-600 text-lg"></i></div>
            <div class="flex-1">
                <div class="font-bold text-gray-800">Atajos de teclado</div>
                <div class="text-xs text-gray-400">Navega entre módulos sin usar el mouse</div>
            </div>
            <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
        </div>
        <div class="help-card-body open">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="shortcut-key">F1</span>
                    <span>Ir a <strong>Ventas / POS</strong></span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="shortcut-key">F2</span>
                    <span>Ir a <strong>Créditos</strong></span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="shortcut-key">F3</span>
                    <span>Ir a <strong>Productos</strong></span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="shortcut-key">F4</span>
                    <span>Ir a <strong>Inventario</strong></span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="shortcut-key">F12</span>
                    <span><strong>Cobrar</strong> (en pantalla de venta)</span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="shortcut-key">Enter</span>
                    <span>Buscar producto por código de barras</span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="shortcut-key">Esc</span>
                    <span>Cerrar cualquier ventana / modal</span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="shortcut-key">Tab</span>
                    <span>Moverse al siguiente campo del formulario</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MÓDULO: PUNTO DE VENTA -->
    <div class="help-card" id="section-pos">
        <div class="help-card-header" onclick="toggleCard(this)">
            <div class="module-icon bg-green-100"><i class="fas fa-cash-register text-green-600 text-lg"></i></div>
            <div class="flex-1">
                <div class="font-bold text-gray-800">Módulo F1 — Punto de Venta (POS)</div>
                <div class="text-xs text-gray-400">Cómo registrar ventas, cobrar y emitir tickets</div>
            </div>
            <i class="fas fa-chevron-down text-gray-400"></i>
        </div>
        <div class="help-card-body">
            <h4 class="font-bold text-gray-700 mb-3 text-sm uppercase tracking-wide">Cómo hacer una venta</h4>
            <div class="space-y-3 mb-4">
                <div class="flex gap-3 items-start text-sm">
                    <span class="step-num mt-0.5">1</span>
                    <div><strong>Busca el producto</strong> — Escanea el código de barras con el lector, o escribe el nombre o código manualmente y presiona <span class="shortcut-key">Enter</span>. El producto se agrega al ticket automáticamente.</div>
                </div>
                <div class="flex gap-3 items-start text-sm">
                    <span class="step-num mt-0.5">2</span>
                    <div><strong>Ajusta la cantidad</strong> — Usa los botones <strong>+</strong> y <strong>−</strong> del ticket para cambiar cuántas unidades. También puedes hacer clic directo sobre el número y escribir la cantidad.</div>
                </div>
                <div class="flex gap-3 items-start text-sm">
                    <span class="step-num mt-0.5">3</span>
                    <div><strong>Presiona F12 o el botón verde "Cobrar"</strong> — Se abre la ventana de pago. Selecciona el método: Efectivo, Tarjeta o QR/Transferencia.</div>
                </div>
                <div class="flex gap-3 items-start text-sm">
                    <span class="step-num mt-0.5">4</span>
                    <div><strong>Si es efectivo</strong>, escribe el monto que entrega el cliente o presiona uno de los botones de billete (Bs.20, 50, 100). El sistema calcula el cambio automáticamente.</div>
                </div>
                <div class="flex gap-3 items-start text-sm">
                    <span class="step-num mt-0.5">5</span>
                    <div><strong>Confirmar pago</strong> — Haz clic en "Confirmar Pago". El sistema registra la venta, descuenta el stock y te permite imprimir el ticket.</div>
                </div>
            </div>
            <div class="tip-box">
                <i class="fas fa-lightbulb mr-2"></i><strong>Consejo:</strong> Si no encuentras un producto por código de barras, revisa que el lector esté enfocado en el campo de búsqueda (debe parpadear el cursor).
            </div>
            <div class="warn-box">
                <i class="fas fa-exclamation-triangle mr-2"></i><strong>Importante:</strong> Si un producto aparece en rojo o no se puede agregar, significa que no tiene stock disponible. Avisa al encargado de inventario.
            </div>
        </div>
    </div>

    <!-- MÓDULO: VENTAS / HISTORIAL -->
    <div class="help-card" id="section-ventas">
        <div class="help-card-header" onclick="toggleCard(this)">
            <div class="module-icon bg-blue-100"><i class="fas fa-receipt text-blue-600 text-lg"></i></div>
            <div class="flex-1">
                <div class="font-bold text-gray-800">Módulo — Historial de Ventas</div>
                <div class="text-xs text-gray-400">Consultar, reimprimir tickets y cancelar ventas</div>
            </div>
            <i class="fas fa-chevron-down text-gray-400"></i>
        </div>
        <div class="help-card-body">
            <div class="space-y-3 text-sm">
                <div class="flex gap-3 items-start">
                    <div class="w-6 h-6 bg-blue-600 rounded text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-search"></i></div>
                    <div><strong>Buscar una venta</strong> — Usa los filtros en la parte superior: por fecha, estado (Completada / Cancelada) o método de pago. También puedes escribir el folio directamente.</div>
                </div>
                <div class="flex gap-3 items-start">
                    <div class="w-6 h-6 bg-green-600 rounded text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-eye"></i></div>
                    <div><strong>Ver detalle de una venta</strong> — Haz clic en el botón "Ver detalle" de cualquier fila. Se abre una ventana con todos los productos vendidos, subtotal, IVA y total.</div>
                </div>
                <div class="flex gap-3 items-start">
                    <div class="w-6 h-6 bg-purple-600 rounded text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-print"></i></div>
                    <div><strong>Reimprimir ticket</strong> — Dentro del detalle de la venta, presiona el botón "Imprimir Ticket". Solo imprime el ticket, no el resto de la pantalla.</div>
                </div>
                <div class="flex gap-3 items-start">
                    <div class="w-6 h-6 bg-red-500 rounded text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-ban"></i></div>
                    <div><strong>Cancelar una venta</strong> — Abre el detalle y presiona "Cancelar Venta". El sistema pedirá confirmación y automáticamente devolverá el stock de todos los productos.</div>
                </div>
                <div class="flex gap-3 items-start">
                    <div class="w-6 h-6 bg-green-700 rounded text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-file-excel"></i></div>
                    <div><strong>Exportar a Excel</strong> — El botón verde "Exportar a Excel" descarga un archivo .xlsx con todas las ventas visibles, ya con formato profesional.</div>
                </div>
            </div>
            <div class="warn-box mt-4">
                <i class="fas fa-exclamation-triangle mr-2"></i><strong>Una venta cancelada no se puede recuperar.</strong> Si cancelaste por error, deberás volver a registrar la venta manualmente.
            </div>
        </div>
    </div>

    <!-- MÓDULO: INVENTARIO -->
    <div class="help-card" id="section-inventario">
        <div class="help-card-header" onclick="toggleCard(this)">
            <div class="module-icon bg-teal-100"><i class="fas fa-warehouse text-teal-600 text-lg"></i></div>
            <div class="flex-1">
                <div class="font-bold text-gray-800">Módulo F4 — Inventario</div>
                <div class="text-xs text-gray-400">Controlar el stock, agregar productos y hacer ajustes</div>
            </div>
            <i class="fas fa-chevron-down text-gray-400"></i>
        </div>
        <div class="help-card-body">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="p-3 bg-red-50 rounded-lg border border-red-200">
                    <div class="font-bold text-red-700 text-sm mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>Productos bajos</div>
                    <div class="text-xs text-red-600">Lista de todos los productos que llegaron o están por debajo del stock mínimo. Revisarla diariamente.</div>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="font-bold text-blue-700 text-sm mb-1"><i class="fas fa-list mr-1"></i>Reporte de inventario</div>
                    <div class="text-xs text-blue-600">Muestra todos los productos con su stock actual, precio y estado. Exportable a Excel.</div>
                </div>
                <div class="p-3 bg-green-50 rounded-lg border border-green-200">
                    <div class="font-bold text-green-700 text-sm mb-1"><i class="fas fa-plus mr-1"></i>Agregar producto</div>
                    <div class="text-xs text-green-600">Registra un producto nuevo con código de barras, precio y stock inicial.</div>
                </div>
                <div class="p-3 bg-orange-50 rounded-lg border border-orange-200">
                    <div class="font-bold text-orange-700 text-sm mb-1"><i class="fas fa-edit mr-1"></i>Ajustes de stock</div>
                    <div class="text-xs text-orange-600">Corrige el stock manualmente: entrada (recibiste mercancía), salida (pérdida/robo), corrección (contar físico).</div>
                </div>
            </div>
            <div class="tip-box">
                <i class="fas fa-lightbulb mr-2"></i><strong>Tipos de ajuste:</strong> "Entrada" suma al stock. "Salida" resta. "Corrección" establece el número exacto que contaste físicamente.
            </div>
        </div>
    </div>

    <!-- MÓDULO: CLIENTES -->
    <div class="help-card" id="section-clientes">
        <div class="help-card-header" onclick="toggleCard(this)">
            <div class="module-icon bg-purple-100"><i class="fas fa-users text-purple-600 text-lg"></i></div>
            <div class="flex-1">
                <div class="font-bold text-gray-800">Módulo — Clientes</div>
                <div class="text-xs text-gray-400">Registrar y editar clientes frecuentes</div>
            </div>
            <i class="fas fa-chevron-down text-gray-400"></i>
        </div>
        <div class="help-card-body">
            <div class="space-y-3 text-sm">
                <div class="flex gap-3 items-start">
                    <span class="step-num mt-0.5">1</span>
                    <div>Haz clic en <strong>"+ Nuevo Cliente"</strong> (arriba a la derecha o en la barra de búsqueda).</div>
                </div>
                <div class="flex gap-3 items-start">
                    <span class="step-num mt-0.5">2</span>
                    <div>Llena el nombre (obligatorio), teléfono, email, NIT y dirección. El <strong>límite de crédito</strong> permite controlar hasta qué monto puede fiarse.</div>
                </div>
                <div class="flex gap-3 items-start">
                    <span class="step-num mt-0.5">3</span>
                    <div>Haz clic en <strong>"Guardar Cliente"</strong>. El cliente quedará registrado y podrás buscarlo por nombre, teléfono o email.</div>
                </div>
                <div class="flex gap-3 items-start">
                    <div class="w-6 h-6 bg-blue-500 rounded text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-edit"></i></div>
                    <div>Para <strong>editar</strong>, haz clic en el ícono de lápiz azul en la fila del cliente.</div>
                </div>
                <div class="flex gap-3 items-start">
                    <div class="w-6 h-6 bg-red-500 rounded text-white text-xs flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-trash"></i></div>
                    <div>Para <strong>eliminar</strong>, haz clic en el ícono de basurero rojo. El sistema pedirá confirmación antes de borrar.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- MÓDULO: REPORTES -->
    <div class="help-card" id="section-reportes">
        <div class="help-card-header" onclick="toggleCard(this)">
            <div class="module-icon bg-indigo-100"><i class="fas fa-chart-bar text-indigo-600 text-lg"></i></div>
            <div class="flex-1">
                <div class="font-bold text-gray-800">Módulo — Reportes</div>
                <div class="text-xs text-gray-400">Analizar ventas con gráficas y estadísticas</div>
            </div>
            <i class="fas fa-chevron-down text-gray-400"></i>
        </div>
        <div class="help-card-body">
            <div class="space-y-2 text-sm">
                <div class="p-3 bg-gray-50 rounded-lg"><strong>Ventas de la semana</strong> — Gráfico de barras con el total vendido por día en los últimos 7 días.</div>
                <div class="p-3 bg-gray-50 rounded-lg"><strong>Por método de pago</strong> — Cuánto se cobró en efectivo, tarjeta y QR esta semana.</div>
                <div class="p-3 bg-gray-50 rounded-lg"><strong>Productos más vendidos</strong> — Los 10 artículos con más unidades vendidas en los últimos 30 días.</div>
                <div class="p-3 bg-gray-50 rounded-lg"><strong>Ganancia por categoría</strong> — Qué tipo de bebidas generó más ganancia (diferencia entre precio de venta y costo).</div>
            </div>
            <div class="tip-box mt-3">
                <i class="fas fa-lightbulb mr-2"></i>Los reportes se actualizan en tiempo real cada vez que entras a la página.
            </div>
        </div>
    </div>

    <!-- PROBLEMAS COMUNES -->
    <div class="help-card" style="border-left: 4px solid #dc2626;" id="section-problemas">
        <div class="help-card-header" onclick="toggleCard(this)">
            <div class="module-icon bg-red-100"><i class="fas fa-tools text-red-600 text-lg"></i></div>
            <div class="flex-1">
                <div class="font-bold text-gray-800">Problemas comunes y soluciones</div>
                <div class="text-xs text-gray-400">Qué hacer cuando algo no funciona</div>
            </div>
            <i class="fas fa-chevron-down text-gray-400"></i>
        </div>
        <div class="help-card-body">
            <div class="space-y-4">
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-red-50 px-4 py-2 text-sm font-bold text-red-700"><i class="fas fa-barcode mr-2"></i>El lector de código de barras no agrega el producto</div>
                    <div class="px-4 py-3 text-sm text-gray-600 space-y-1">
                        <div>→ Verifica que el cursor esté en el campo de búsqueda (debe verse el cursor parpadeando).</div>
                        <div>→ Haz clic una vez sobre el campo de búsqueda y vuelve a escanear.</div>
                        <div>→ Si el código no existe, el producto no está registrado en el sistema — hay que agregarlo primero en Productos.</div>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-red-50 px-4 py-2 text-sm font-bold text-red-700"><i class="fas fa-print mr-2"></i>El ticket no imprime bien (sale muy grande o cortado)</div>
                    <div class="px-4 py-3 text-sm text-gray-600 space-y-1">
                        <div>→ Verifica que en las opciones de impresión esté seleccionada la impresora térmica correcta.</div>
                        <div>→ En el diálogo de impresión, en "Más ajustes", desactiva los márgenes y selecciona tamaño de papel personalizado (80mm).</div>
                        <div>→ Si imprime en hoja A4 normal, selecciona la impresora de tickets, no la de hojas.</div>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-red-50 px-4 py-2 text-sm font-bold text-red-700"><i class="fas fa-lock mr-2"></i>No puedo entrar al sistema / contraseña incorrecta</div>
                    <div class="px-4 py-3 text-sm text-gray-600 space-y-1">
                        <div>→ Verifica que el Bloq Mayús (<span class="shortcut-key">Caps Lock</span>) no esté activado.</div>
                        <div>→ La contraseña distingue mayúsculas y minúsculas.</div>
                        <div>→ Si olvidaste la contraseña, contacta al administrador del sistema.</div>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-red-50 px-4 py-2 text-sm font-bold text-red-700"><i class="fas fa-wifi mr-2"></i>La página no carga o se ve en blanco</div>
                    <div class="px-4 py-3 text-sm text-gray-600 space-y-1">
                        <div>→ Verifica que tengas conexión a internet o a la red local.</div>
                        <div>→ Recarga la página con <span class="shortcut-key">F5</span> o <span class="shortcut-key">Ctrl+R</span>.</div>
                        <div>→ Si el problema persiste, avisa al técnico responsable del servidor.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contacto soporte -->
    <div class="card p-5 flex items-center gap-5 mt-2">
        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
            <i class="fas fa-headset text-blue-600 text-xl"></i>
        </div>
        <div>
            <div class="font-bold text-gray-800">¿No encontraste la respuesta?</div>
            <div class="text-sm text-gray-500 mt-1">Contacta al administrador del sistema o al técnico responsable de tu sucursal.</div>
        </div>
        <div class="ml-auto text-right text-sm text-gray-400">
            <div>Sistema v1.0</div>
            <div>elixirdorado.com.bo</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleCard(header) {
    const body = header.nextElementSibling;
    const icon = header.querySelector('.fa-chevron-down');
    const isOpen = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    icon.style.transform = isOpen ? '' : 'rotate(180deg)';
}

function abrirTodo() {
    document.querySelectorAll('.help-card-body').forEach(b => b.classList.add('open'));
    document.querySelectorAll('.fa-chevron-down').forEach(i => i.style.transform = 'rotate(180deg)');
}

function buscarAyuda(q) {
    q = q.toLowerCase().trim();
    if (!q) {
        document.querySelectorAll('.help-card').forEach(c => c.style.display = '');
        return;
    }
    document.querySelectorAll('.help-card').forEach(card => {
        const text = card.textContent.toLowerCase();
        const match = text.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) {
            card.querySelector('.help-card-body').classList.add('open');
        }
    });
}
</script>
@endsection
