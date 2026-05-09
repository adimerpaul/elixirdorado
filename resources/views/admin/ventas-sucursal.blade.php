<!DOCTYPE html>
<html>
<head>
    <title>Elixirdorado - Ventas {{ $sucursal->nombre }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-white shadow-md">
            <div class="container mx-auto px-4 py-3">
                <a href="/admin/reportes" class="text-blue-600 hover:underline">← Volver a reportes</a>
                <h1 class="text-2xl font-bold mt-2">{{ $sucursal->nombre }} - Ventas</h1>
            </div>
        </nav>
        
        <div class="container mx-auto px-4 py-8">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-3">Folio</th>
                            <th class="text-left p-3">Fecha</th>
                            <th class="text-right p-3">Subtotal</th>
                            <th class="text-right p-3">IVA</th>
                            <th class="text-right p-3">Total</th>
                            <th class="text-left p-3">Método</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ventas as $venta)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-medium">{{ $venta->folio }}</td>
                            <td class="p-3">{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y H:i') }}</td>
                            <td class="p-3 text-right">Bs. {{ number_format($venta->subtotal, 2) }}</td>
                            <td class="p-3 text-right">Bs. {{ number_format($venta->iva, 2) }}</td>
                            <td class="p-3 text-right font-bold">Bs. {{ number_format($venta->total, 2) }}</td>
                            <td class="p-3">{{ ucfirst($venta->metodo_pago) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4">
                    {{ $ventas->links() }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>