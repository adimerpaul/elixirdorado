@extends('layouts.sucursal')
@section('titulo', 'Dashboard')
@section('content')
<div class="section-bar flex items-center justify-between">
    <span class="font-bold text-blue-900 text-lg">Dashboard - {{ $sucursal->nombre }}</span>
    <div class="flex items-center gap-2 text-sm text-gray-600">
        <i class="fas fa-map-marker-alt text-red-400"></i>
        {{ $sucursal->direccion ?? 'Bolivia' }}
        @if($sucursal->telefono) &nbsp;|&nbsp; <i class="fas fa-phone text-green-500"></i> {{ $sucursal->telefono }} @endif
    </div>
</div>

<div class="p-4" style="padding-bottom:60px;">

    <!-- Stats cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
        <div class="card p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Ventas Hoy</div>
                    <div class="text-2xl font-black text-green-700 mt-1">Bs. {{ number_format($ventasHoy ?? 0, 2) }}</div>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="text-xs text-gray-500 mt-2"><i class="fas fa-calendar-day mr-1"></i>{{ now()->format('d/m/Y') }}</div>
        </div>
        <div class="card p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Total Productos</div>
                    <div class="text-2xl font-black text-blue-700 mt-1">{{ $totalProductos ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-box text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="text-xs text-gray-500 mt-2">productos registrados</div>
        </div>
        <div class="card p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Bajo Stock</div>
                    <div class="text-2xl font-black {{ ($productosBajoStock ?? 0) > 0 ? 'text-red-700' : 'text-gray-700' }} mt-1">{{ $productosBajoStock ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
            <div class="text-xs text-gray-500 mt-2">
                @if(($productosBajoStock ?? 0) > 0)
                    <span class="text-red-600 font-bold">⚠ Requieren atención</span>
                @else
                    <span class="text-green-600">✓ Todo en orden</span>
                @endif
            </div>
        </div>
        <div class="card p-4 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-gray-500 uppercase font-bold tracking-wide">Ventas del Mes</div>
                    <div class="text-2xl font-black text-purple-700 mt-1">-</div>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                </div>
            </div>
            <div class="text-xs text-gray-500 mt-2">{{ now()->format('F Y') }}</div>
        </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <a href="/{{ $sucursal->slug }}/pos" class="card p-4 text-center hover:shadow-md transition-shadow group cursor-pointer border-2 hover:border-green-400">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-green-200">
                <i class="fas fa-cash-register text-green-600 text-2xl"></i>
            </div>
            <div class="font-bold text-gray-800">Punto de Venta</div>
            <div class="text-xs text-gray-500">F1 - POS</div>
        </a>
        <a href="/{{ $sucursal->slug }}/inventario" class="card p-4 text-center hover:shadow-md transition-shadow group cursor-pointer border-2 hover:border-teal-400">
            <div class="w-14 h-14 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-teal-200">
                <i class="fas fa-warehouse text-teal-600 text-2xl"></i>
            </div>
            <div class="font-bold text-gray-800">Inventario</div>
            <div class="text-xs text-gray-500">F4 - Stock</div>
        </a>
        <a href="/{{ $sucursal->slug }}/ventas" class="card p-4 text-center hover:shadow-md transition-shadow group cursor-pointer border-2 hover:border-blue-400">
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-blue-200">
                <i class="fas fa-receipt text-blue-600 text-2xl"></i>
            </div>
            <div class="font-bold text-gray-800">Ventas</div>
            <div class="text-xs text-gray-500">Historial</div>
        </a>
        <a href="/{{ $sucursal->slug }}/reportes" class="card p-4 text-center hover:shadow-md transition-shadow group cursor-pointer border-2 hover:border-orange-400">
            <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-orange-200">
                <i class="fas fa-chart-bar text-orange-600 text-2xl"></i>
            </div>
            <div class="font-bold text-gray-800">Reportes</div>
            <div class="text-xs text-gray-500">Estadísticas</div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Tabla de productos recientes -->
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="p-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-bold text-gray-800"><i class="fas fa-box mr-2 text-blue-500"></i>Productos</h3>
                <a href="/{{ $sucursal->slug }}/productos" class="text-blue-600 text-sm hover:underline">Ver todos →</a>
            </div>
            <table class="w-full text-sm">
                <thead class="table-header">
                    <tr>
                        <th class="text-left p-2">Nombre</th>
                        <th class="text-right p-2">Precio</th>
                        <th class="text-center p-2">Stock</th>
                        <th class="text-center p-2">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos->take(8) as $prod)
                    <tr class="table-row border-b">
                        <td class="p-2 font-medium text-gray-800">{{ $prod->nombre }}</td>
                        <td class="p-2 text-right text-green-700 font-bold">Bs. {{ number_format($prod->precio_venta, 2) }}</td>
                        <td class="p-2 text-center font-bold {{ $prod->stock_actual <= ($prod->stock_minimo ?? 5) ? 'text-red-600' : 'text-gray-700' }}">
                            {{ $prod->stock_actual }}
                        </td>
                        <td class="p-2 text-center">
                            @if($prod->stock_actual <= 0)
                                <span class="badge-red">Sin stock</span>
                            @elseif($prod->stock_actual <= ($prod->stock_minimo ?? 5))
                                <span class="badge-yellow">Bajo</span>
                            @else
                                <span class="badge-green">OK</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-8 text-gray-400">
                        <i class="fas fa-box text-3xl mb-2 block opacity-30"></i>
                        No hay productos. <a href="/{{ $sucursal->slug }}/productos" class="text-blue-600 underline">Agregar productos</a>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Panel lateral -->
        <div class="flex flex-col gap-4">
            <!-- Acceso rápido al POS -->
            <a href="/{{ $sucursal->slug }}/pos" class="card p-5 text-center block"
               style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); color:white;">
                <i class="fas fa-cash-register text-4xl mb-2 block text-yellow-300"></i>
                <div class="font-black text-xl">ABRIR POS</div>
                <div class="text-blue-200 text-sm mt-1">Punto de Venta</div>
            </a>

            <!-- Alertas de bajo stock -->
            @if(($productosBajoStock ?? 0) > 0)
            <div class="card p-4 border-l-4 border-red-500">
                <div class="font-bold text-red-700 mb-2"><i class="fas fa-exclamation-triangle mr-1"></i>Bajo Stock</div>
                @foreach($productos->where('stock_actual', '<=', 5)->take(5) as $p)
                <div class="flex items-center justify-between py-1 text-sm border-b border-gray-100 last:border-0">
                    <span class="text-gray-700 truncate">{{ $p->nombre }}</span>
                    <span class="badge-red ml-2">{{ $p->stock_actual }}</span>
                </div>
                @endforeach
                <a href="/{{ $sucursal->slug }}/inventario" class="text-red-600 text-xs hover:underline block mt-2">Ver inventario →</a>
            </div>
            @endif

            <!-- Accesos directos -->
            <div class="card p-4">
                <div class="font-bold text-gray-700 mb-3 text-sm">Accesos Directos</div>
                <div class="flex flex-col gap-2">
                    <a href="/{{ $sucursal->slug }}/clientes" class="nav-btn justify-start"><i class="fas fa-users text-blue-500 w-5"></i>Clientes</a>
                    <a href="/{{ $sucursal->slug }}/facturas" class="nav-btn justify-start"><i class="fas fa-file-invoice text-red-500 w-5"></i>Facturas</a>
                    <a href="/{{ $sucursal->slug }}/corte" class="nav-btn justify-start"><i class="fas fa-cut text-pink-500 w-5"></i>Corte de Caja</a>
                    <a href="/{{ $sucursal->slug }}/configuracion" class="nav-btn justify-start"><i class="fas fa-cog text-gray-500 w-5"></i>Configuración</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
