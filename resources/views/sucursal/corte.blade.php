@extends('layouts.sucursal')
@section('titulo', 'Corte de Caja')
@section('content')
<div class="section-bar flex items-center justify-between">
    <span class="font-bold text-blue-900 text-lg">CORTE DE CAJA</span>
    <span class="text-sm text-gray-600"><i class="fas fa-calendar-alt mr-1"></i>{{ now()->format('d/m/Y') }}</span>
</div>
<div class="p-6" style="padding-bottom:60px;">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card p-5 border-l-4 border-green-500">
            <div class="text-sm text-gray-500 mb-1">Efectivo en Caja</div>
            <div class="text-3xl font-black text-green-700">Bs. {{ number_format($ventasHoy ?? 0, 2) }}</div>
            <div class="text-xs text-gray-400 mt-1">Ventas del día</div>
        </div>
        @foreach($ventasPorMetodo ?? [] as $v)
        <div class="card p-5 border-l-4 border-blue-500">
            <div class="text-sm text-gray-500 mb-1 capitalize">{{ $v->metodo_pago }}</div>
            <div class="text-3xl font-black text-blue-700">Bs. {{ number_format($v->total, 2) }}</div>
        </div>
        @endforeach
    </div>
    <div class="card p-6 max-w-lg mx-auto text-center">
        <i class="fas fa-cut text-pink-500 text-5xl mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Corte de Caja</h2>
        <p class="text-gray-600 mb-6">Resumen del día: <strong>{{ now()->format('d/m/Y') }}</strong></p>
        <div class="bg-gray-50 rounded-lg p-4 text-left mb-6">
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="text-gray-600">Total Ventas del Día:</span>
                <span class="font-bold text-lg">Bs. {{ number_format($ventasHoy ?? 0, 2) }}</span>
            </div>
            @foreach($ventasPorMetodo ?? [] as $v)
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="text-gray-600 capitalize">{{ $v->metodo_pago }}:</span>
                <span class="font-medium">Bs. {{ number_format($v->total, 2) }}</span>
            </div>
            @endforeach
        </div>
        <button onclick="window.print()" class="btn-primary w-full py-3 text-lg mb-3">
            <i class="fas fa-print mr-2"></i>Imprimir Corte
        </button>
        <a href="/{{ $sucursal->slug }}/ventas" class="btn-secondary w-full py-3 inline-block text-center">
            <i class="fas fa-list mr-2"></i>Ver Ventas del Día
        </a>
    </div>
</div>
@endsection
