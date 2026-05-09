@extends('layouts.sucursal')
@section('titulo', 'Compras')
@section('content')
<div class="section-bar"><span class="font-bold text-blue-900 text-lg">COMPRAS / ENTRADAS DE INVENTARIO</span></div>
<div class="p-6 flex flex-col items-center justify-center" style="min-height:400px;padding-bottom:60px;">
    <i class="fas fa-shopping-cart text-indigo-400 text-6xl mb-4 opacity-50"></i>
    <h2 class="text-2xl font-bold text-gray-700 mb-2">Módulo de Compras</h2>
    <p class="text-gray-500 mb-6">Registra entradas de inventario y órdenes de compra a proveedores.</p>
    <a href="/{{ $sucursal->slug }}/inventario" class="btn-primary px-6 py-3"><i class="fas fa-warehouse mr-2"></i>Ir a Inventario</a>
</div>
@endsection
