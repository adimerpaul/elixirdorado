@extends('layouts.sucursal')
@section('titulo', 'Facturas')

@section('extra-styles')
/* ── IMPRESIÓN FACTURA (A4) ─────────────────────────────── */
@media print {
    .no-print, .topbar, .module-bar, .section-bar, .status-bar,
    nav, .flex.gap-2.mb-4, .flex.gap-2.mt-3 { display: none !important; }
    body { background: white !important; font-size: 12px; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    table { font-size: 11px; }
    @page { size: A4; margin: 15mm; }
}
@endsection

@section('content')
<div class="section-bar flex items-center justify-between">
    <span class="font-bold text-blue-900 text-lg">FACTURACIÓN</span>
</div>
<div class="p-4" style="padding-bottom:60px;">
    <div class="flex gap-0 mb-4 border-b border-gray-300">
        <button class="px-5 py-2 text-sm font-semibold border-b-2 border-blue-600 text-blue-700 bg-white">Facturas por Ventas</button>
        <button class="px-5 py-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700">Facturas Globales</button>
        <button class="px-5 py-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700">Clientes de facturación</button>
    </div>
    <div class="card p-4 mb-4">
        <h2 class="text-lg font-bold text-blue-900 mb-4">FACTURAS POR VENTAS</h2>
        <div class="flex items-center gap-3 mb-4">
            <span class="text-sm text-gray-600">Mostrar facturas de:</span>
            <select class="input-field" style="width:auto;"><option>{{ now()->format('F') }}</option></select>
            <select class="input-field" style="width:auto;"><option>{{ now()->year }}</option></select>
            <div class="ml-auto">
                <button class="btn-secondary text-sm"><i class="fas fa-file-excel mr-1 text-green-600"></i>Exportar a Excel</button>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="table-header"><tr>
                <th class="text-left p-2">Fecha</th><th class="text-left p-2">Serie</th><th class="text-left p-2">Folio</th>
                <th class="text-left p-2">Cliente</th><th class="text-right p-2">Subtotal</th><th class="text-right p-2">Impuestos</th>
                <th class="text-right p-2">Total</th><th class="text-left p-2">Tipo</th><th class="text-left p-2">Estado</th>
            </tr></thead>
            <tbody>
                @forelse($ventas ?? [] as $v)
                <tr class="table-row border-b">
                    <td class="p-2">{{ \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y') }}</td>
                    <td class="p-2">A</td>
                    <td class="p-2 font-mono text-xs">{{ $v->folio }}</td>
                    <td class="p-2">Público General</td>
                    <td class="p-2 text-right">Bs. {{ number_format($v->subtotal ?? 0, 2) }}</td>
                    <td class="p-2 text-right">Bs. {{ number_format($v->iva ?? 0, 2) }}</td>
                    <td class="p-2 text-right font-bold">Bs. {{ number_format($v->total, 2) }}</td>
                    <td class="p-2"><span class="badge-blue">Venta</span></td>
                    <td class="p-2"><span class="badge-green">Activa</span></td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-12 text-gray-400"><i class="fas fa-file-invoice text-3xl mb-2 block opacity-30"></i>0 facturas</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="flex items-center justify-between mt-3 text-sm text-gray-600 border-t pt-3">
            <span>{{ count($ventas ?? []) }} facturas</span>
            <div class="flex gap-8">
                <span>Bs. {{ number_format(collect($ventas ?? [])->sum('subtotal'), 2) }}</span>
                <span>Bs. {{ number_format(collect($ventas ?? [])->sum('iva'), 2) }}</span>
                <span class="font-bold">Bs. {{ number_format(collect($ventas ?? [])->sum('total'), 2) }}</span>
            </div>
        </div>
    </div>
    <div class="flex gap-2">
        <button class="nav-btn"><i class="fas fa-desktop text-blue-500"></i>Ver en pantalla</button>
        <button onclick="window.print()" class="nav-btn"><i class="fas fa-print text-gray-600"></i>Imprimir...</button>
        <button class="nav-btn"><i class="fas fa-download text-green-500"></i>Guardar copia</button>
        <button class="nav-btn"><i class="fas fa-envelope text-red-400"></i>Enviar por correo</button>
        <div class="ml-auto">
            <button class="btn-danger text-sm"><i class="fas fa-ban mr-1"></i>Solicitar cancelación</button>
        </div>
    </div>
</div>
@endsection
