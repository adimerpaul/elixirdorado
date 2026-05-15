<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Productos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .meta { color: #6b7280; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1f2937; color: white; text-align: left; padding: 7px; font-size: 10px; }
        td { border-bottom: 1px solid #e5e7eb; padding: 6px 7px; }
        .num { text-align: right; }
        .center { text-align: center; }
        .inactive { color: #9ca3af; }
    </style>
</head>
<body>
    <h1>Productos - {{ $sucursal->nombre }}</h1>
    <div class="meta">
        {{ $scope === 'existing' ? 'Productos con cantidad en compras activas' : 'Todos los productos' }}
        | Generado: {{ now()->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Codigo</th>
                <th>Producto</th>
                <th>Categoria</th>
                <th class="center">Cantidad</th>
                <th class="center">Stock</th>
                <th class="num">P. Compra</th>
                <th class="num">P. Venta</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productos as $producto)
                <tr class="{{ $producto->activo ? '' : 'inactive' }}">
                    <td>{{ $producto->codigo_barras ?? '-' }}</td>
                    <td>{{ $producto->nombre }}</td>
                    <td>{{ $producto->categoria->nombre ?? 'Sin categoria' }}</td>
                    <td class="center">{{ (int) ($producto->cantidad_compras_activas ?? 0) }}</td>
                    <td class="center">{{ (int) $producto->stock_actual }}</td>
                    <td class="num">Bs. {{ number_format((float) $producto->precio_compra, 2) }}</td>
                    <td class="num">Bs. {{ number_format((float) $producto->precio_venta, 2) }}</td>
                    <td>{{ $producto->activo ? 'Activo' : 'Inactivo' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">No hay productos para exportar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
