@extends('layouts.sucursal')
@section('titulo', 'Créditos')
@section('content')
<div class="section-bar"><span class="font-bold text-blue-900 text-lg">CRÉDITOS Y CUENTAS POR COBRAR</span></div>
<div class="p-6 flex flex-col items-center justify-center" style="min-height:400px;padding-bottom:60px;">
    <i class="fas fa-credit-card text-purple-400 text-6xl mb-4 opacity-50"></i>
    <h2 class="text-2xl font-bold text-gray-700 mb-2">Módulo de Créditos</h2>
    <p class="text-gray-500 mb-6">Administra ventas a crédito y seguimiento de pagos.</p>
    <a href="/{{ $sucursal->slug }}/clientes" class="btn-primary px-6 py-3"><i class="fas fa-users mr-2"></i>Ver Clientes</a>
</div>
@endsection
