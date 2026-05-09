<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel Administrador - elixirdorado.com.bo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e3a5f;
            --primary-soft: #2c4f7c;
            --gold: #d4a574;
            --gold-soft: #e8c79a;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .topbar { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-soft) 100%); }
        .accent-gold { color: var(--gold); }
        .btn-gold {
            background: linear-gradient(180deg, var(--gold) 0%, #c19660 100%);
            color: var(--primary); border: 1px solid #b8865a;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .btn-gold:hover { filter: brightness(1.05); }
        .card-hover { transition: transform .25s ease, box-shadow .25s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(30,58,95,0.12); }
        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-inactive { background-color: #fee2e2; color: #7f1d1d; }

        /* KPI cards con cinta lateral de color */
        .kpi { position: relative; overflow: hidden; }
        .kpi::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
            background: var(--accent, var(--gold));
        }
        .kpi-icon-wrap {
            width: 52px; height: 52px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: var(--accent-bg, #fef3c7); color: var(--accent, var(--gold));
            font-size: 22px;
        }

        /* Tarjeta de sucursal */
        .sucursal-card { border-top: 3px solid var(--gold); }
        .sucursal-card .acciones a {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            padding: 8px 6px; border-radius: 6px; font-size: 12px; font-weight: 600;
            transition: transform .15s ease, filter .15s ease;
        }
        .sucursal-card .acciones a:hover { transform: translateY(-1px); filter: brightness(1.05); }

        /* Errores de validación */
        .error-banner {
            background: #fef2f2; border-left: 4px solid #dc2626; color: #991b1b;
            padding: 12px 16px; border-radius: 6px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Topbar -->
    <nav class="topbar text-white px-6 py-4 shadow-lg">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">🥃</span>
                <div>
                    <h1 class="text-xl font-bold">elixirdorado.com.bo</h1>
                    <p class="text-xs accent-gold">Panel Administrador</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="flex items-center space-x-2 hover:text-gray-300 transition">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar sesión</span>
                </button>
            </form>
        </div>
    </nav>

    <!-- Navbar -->
    <div class="bg-white border-b border-gray-200 px-6 py-4 shadow-sm">
        <div class="max-w-7xl mx-auto flex space-x-8">
            <a href="/admin" class="accent-gold font-semibold pb-2 border-b-2" style="border-color: var(--gold);">
                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </a>
            <a href="#" class="text-gray-600 hover:accent-gold transition pb-2">
                <i class="fas fa-store mr-2"></i>Sucursales
            </a>
            <a href="/admin/reportes" class="text-gray-600 hover:accent-gold transition pb-2">
                <i class="fas fa-chart-line mr-2"></i>Reportes Globales
            </a>
            <a href="#" class="text-gray-600 hover:accent-gold transition pb-2">
                <i class="fas fa-cog mr-2"></i>Configuración
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Page Title -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold" style="color: var(--primary);">Panel de Administración General</h2>
            <p class="text-gray-600 mt-2">Gestiona todas las sucursales de elixirdorado desde aquí</p>
        </div>

        <!-- Mensajes -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-lg bg-green-50 border-l-4" style="border-color: var(--gold);">
                <div class="flex items-start">
                    <i class="fas fa-check-circle accent-gold mt-1 mr-3"></i>
                    <p class="text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 error-banner">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle mt-1"></i>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="kpi bg-white rounded-lg shadow p-5 card-hover" style="--accent:#1e3a5f; --accent-bg:#dbeafe;">
                <div class="flex items-center gap-4">
                    <div class="kpi-icon-wrap"><i class="fas fa-store"></i></div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase font-bold tracking-wide">Total Sucursales</p>
                        <p class="text-3xl font-bold mt-1" style="color: var(--primary);">{{ $sucursales->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="kpi bg-white rounded-lg shadow p-5 card-hover" style="--accent:#d4a574; --accent-bg:#fef3c7;">
                <div class="flex items-center gap-4">
                    <div class="kpi-icon-wrap"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase font-bold tracking-wide">Sucursales Activas</p>
                        <p class="text-3xl font-bold mt-1" style="color: var(--gold);">{{ $sucursales->where('activa', true)->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="kpi bg-white rounded-lg shadow p-5 card-hover" style="--accent:#16a34a; --accent-bg:#dcfce7;">
                <div class="flex items-center gap-4">
                    <div class="kpi-icon-wrap"><i class="fas fa-chart-bar"></i></div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase font-bold tracking-wide">Ventas hoy</p>
                        <p class="text-2xl font-bold mt-1" style="color: #16a34a;">Bs. {{ number_format($totalHoyGlobal ?? 0, 2) }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">{{ now()->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            <div class="kpi bg-white rounded-lg shadow p-5 card-hover" style="--accent:#7c3aed; --accent-bg:#ede9fe;">
                <div class="flex items-center gap-4">
                    <div class="kpi-icon-wrap"><i class="fas fa-boxes"></i></div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase font-bold tracking-wide">Ventas del Mes</p>
                        <p class="text-2xl font-bold mt-1" style="color: #7c3aed;">Bs. {{ number_format($totalMesGlobal ?? 0, 2) }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">{{ now()->format('F Y') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sucursales Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold" style="color: var(--primary);">Sucursales</h3>
                <button onclick="openModal()" class="btn-gold hover:opacity-90 px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>Agregar Nueva Sucursal
                </button>
            </div>

            @if($sucursales->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($sucursales as $sucursal)
                    @php $s = $stats[$sucursal->id] ?? []; @endphp
                        <div class="bg-white rounded-lg shadow card-hover overflow-hidden border-t-4" style="border-color: var(--gold);">
                            <div class="p-5">
                                <!-- Header -->
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="text-lg font-bold" style="color: var(--primary);">{{ $sucursal->nombre }}</h4>
                                        <p class="text-xs text-gray-500">localhost:8000/{{ $sucursal->slug }}</p>
                                    </div>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $sucursal->activa ? 'status-active' : 'status-inactive' }}">
                                        {{ $sucursal->activa ? '● Activa' : '○ Inactiva' }}
                                    </span>
                                </div>

                                <!-- Info -->
                                <div class="text-xs text-gray-500 space-y-1 mb-3">
                                    @if($sucursal->direccion)<p><i class="fas fa-map-marker-alt mr-1 accent-gold"></i>{{ $sucursal->direccion }}</p>@endif
                                    @if($sucursal->telefono)<p><i class="fas fa-phone mr-1 accent-gold"></i>{{ $sucursal->telefono }}</p>@endif
                                </div>

                                <!-- Mini stats -->
                                @if(!isset($s['error']))
                                <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                                    <div class="bg-green-50 rounded p-2">
                                        <div class="text-xs text-gray-500">Hoy</div>
                                        <div class="font-black text-green-700 text-sm">Bs.{{ number_format($s['ventas_hoy']??0,0) }}</div>
                                        <div class="text-xs text-gray-400">{{ $s['ventas_count']??0 }} ventas</div>
                                    </div>
                                    <div class="bg-blue-50 rounded p-2">
                                        <div class="text-xs text-gray-500">Mes</div>
                                        <div class="font-black text-blue-700 text-sm">Bs.{{ number_format($s['ventas_mes']??0,0) }}</div>
                                        <div class="text-xs text-gray-400">{{ now()->format('M') }}</div>
                                    </div>
                                    <div class="bg-purple-50 rounded p-2">
                                        <div class="text-xs text-gray-500">Productos</div>
                                        <div class="font-black text-purple-700 text-sm">{{ $s['total_productos']??0 }}</div>
                                        @if(($s['bajo_stock']??0) > 0)
                                        <div class="text-xs text-red-500">⚠ {{ $s['bajo_stock'] }} bajos</div>
                                        @else
                                        <div class="text-xs text-green-500">✓ OK</div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <!-- Action Buttons -->
                                <div class="grid grid-cols-2 gap-2">
                                    <a href="/{{ $sucursal->slug }}/pos" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-xs font-semibold transition text-center">
                                        <i class="fas fa-cash-register mr-1"></i>Abrir POS
                                    </a>
                                    <a href="/{{ $sucursal->slug }}/ventas" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-xs font-semibold transition text-center">
                                        <i class="fas fa-receipt mr-1"></i>Ver Ventas
                                    </a>
                                    <a href="/{{ $sucursal->slug }}/inventario" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded text-xs font-medium transition text-center">
                                        <i class="fas fa-warehouse mr-1"></i>Inventario
                                    </a>
                                    <a href="/{{ $sucursal->slug }}/reportes" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded text-xs font-medium transition text-center">
                                        <i class="fas fa-chart-bar mr-1"></i>Reportes
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600">No hay sucursales registradas aún</p>
                    <button onclick="openModal()" class="btn-gold mt-4 px-6 py-2 rounded-lg font-semibold">
                        <i class="fas fa-plus mr-2"></i>Crear Primera Sucursal
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal: Crear Sucursal -->
    <div id="modalCrearSucursal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="topbar text-white px-6 py-4 flex justify-between items-center sticky top-0">
                <h3 class="text-xl font-bold">Crear Nueva Sucursal</h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-300">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form method="POST" action="/admin/sucursales" class="p-6 space-y-6">
                @csrf

                <!-- Información de la Sucursal -->
                <div>
                    <h4 class="font-bold mb-4" style="color: var(--primary);">Información de la Sucursal</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-store mr-2 accent-gold"></i>Nombre de la Sucursal *
                            </label>
                            <input type="text" name="nombre" placeholder="Ej: Sucursal Centro"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-link mr-2 accent-gold"></i>Slug (identificador) *
                            </label>
                            <input type="text" name="slug" placeholder="Ej: centro"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                required>
                            <p class="text-xs text-gray-500 mt-1">Se usará en la URL: /centro</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-map-marker-alt mr-2 accent-gold"></i>Dirección
                            </label>
                            <input type="text" name="direccion" placeholder="Dirección física de la sucursal"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-phone mr-2 accent-gold"></i>Teléfono
                            </label>
                            <input type="text" name="telefono" placeholder="Número de contacto"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-envelope mr-2 accent-gold"></i>Email
                            </label>
                            <input type="email" name="email" placeholder="contacto@sucursal.com"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                        </div>
                    </div>
                </div>

                <!-- Separador -->
                <hr class="border-gray-200">

                <!-- Administrador -->
                <div>
                    <h4 class="font-bold mb-4" style="color: var(--primary);">
                        <i class="fas fa-user-tie mr-2"></i>Administrador de la Sucursal
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre Completo *
                            </label>
                            <input type="text" name="admin_nombre" placeholder="Nombre del administrador"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email *
                            </label>
                            <input type="email" name="admin_email" placeholder="admin@sucursal.com"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Contraseña *
                            </label>
                            <input type="password" name="admin_password" placeholder="Mínimo 8 caracteres" minlength="8"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                required>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-gold px-6 py-2 rounded-lg font-semibold hover:opacity-90 transition">
                        <i class="fas fa-check mr-2"></i>Crear Sucursal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modalCrearSucursal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modalCrearSucursal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('modalCrearSucursal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
