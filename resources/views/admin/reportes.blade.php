<!DOCTYPE html>
<html>
<head>
    <title>Elixirdorado - Reportes Consolidados</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navbar -->
        <nav class="bg-white shadow-md">
            <div class="container mx-auto px-4 py-3 flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <span class="text-2xl">🥃</span>
                    <h1 class="text-xl font-bold">Elixirdorado - Admin</h1>
                </div>
                <div class="flex space-x-6">
                    <a href="/admin" class="text-gray-700 hover:text-blue-600">Sucursales</a>
                    <a href="/admin/reportes" class="text-gray-700 hover:text-blue-600 font-bold border-b-2 border-blue-600">📊 Reportes</a>
                </div>
            </div>
        </nav>
        
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold mb-6">📊 Reportes Consolidados</h1>
            
            <!-- Tarjetas de totales generales -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">Total General</div>
                    <div class="text-3xl font-bold text-green-600">Bs. {{ number_format($totalGeneral, 2) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">Ventas Hoy</div>
                    <div class="text-3xl font-bold text-blue-600">Bs. {{ number_format($ventasHoyGeneral, 2) }}</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500">Ventas del Mes</div>
                    <div class="text-3xl font-bold text-purple-600">Bs. {{ number_format($ventasMesGeneral, 2) }}</div>
                </div>
            </div>
            
            <!-- Reportes por sucursal -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($reportes as $slug => $reporte)
                    @if(isset($reporte['error']))
                        <div class="bg-red-100 border border-red-400 text-red-700 rounded-lg shadow p-6">
                            <h2 class="text-xl font-bold mb-2">{{ $reporte['sucursal']->nombre }}</h2>
                            <p>Error: {{ $reporte['error'] }}</p>
                        </div>
                    @else
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-4">
                                <h2 class="text-xl font-bold">{{ $reporte['sucursal']->nombre }}</h2>
                                <p class="text-sm opacity-90">{{ $reporte['sucursal']->direccion ?? 'Sin dirección' }}</p>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <div class="text-sm text-gray-500">Ventas hoy</div>
                                        <div class="text-2xl font-bold text-green-600">Bs. {{ number_format($reporte['ventas_hoy'], 2) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Ventas del mes</div>
                                        <div class="text-2xl font-bold text-blue-600">Bs. {{ number_format($reporte['ventas_mes'], 2) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Total acumulado</div>
                                        <div class="text-2xl font-bold text-purple-600">Bs. {{ number_format($reporte['total_ventas'], 2) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">N° de ventas</div>
                                        <div class="text-2xl font-bold text-gray-700">{{ $reporte['cantidad_ventas'] }}</div>
                                    </div>
                                </div>
                                
                                @if($reporte['productos_top']->count() > 0)
                                    <div class="border-t pt-4">
                                        <h3 class="font-bold mb-2">🥇 Productos más vendidos</h3>
                                        <ul class="space-y-1">
                                            @foreach($reporte['productos_top'] as $producto)
                                                <li class="flex justify-between text-sm">
                                                    <span>{{ $producto->nombre }}</span>
                                                    <span class="font-bold">{{ $producto->total_vendido }} unidades</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                
                                <div class="mt-6">
                                    <a href="/admin/reportes/{{ $slug }}/ventas" class="text-blue-600 hover:underline text-sm">
                                        Ver todas las ventas →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</body>
</html>