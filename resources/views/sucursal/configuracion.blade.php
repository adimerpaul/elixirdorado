@extends('layouts.sucursal')
@section('titulo', 'Configuración')
@section('content')
<div class="section-bar"><span class="font-bold text-blue-900 text-lg">CONFIGURACIÓN DE LA SUCURSAL</span></div>
<div class="p-6" style="padding-bottom:60px;">
    <div class="card p-6 max-w-2xl">
        <h2 class="text-xl font-bold mb-4"><i class="fas fa-store mr-2 text-blue-600"></i>Datos de la Sucursal</h2>
        <form method="POST" action="/{{ $sucursal->slug }}/configuracion">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-sm font-medium text-gray-700 block mb-1">Nombre</label>
                    <input type="text" value="{{ $sucursal->nombre }}" class="input-field" readonly></div>
                <div><label class="text-sm font-medium text-gray-700 block mb-1">Teléfono</label>
                    <input type="text" name="telefono" value="{{ $sucursal->telefono }}" class="input-field"></div>
                <div class="col-span-2"><label class="text-sm font-medium text-gray-700 block mb-1">Dirección</label>
                    <input type="text" name="direccion" value="{{ $sucursal->direccion }}" class="input-field"></div>
            </div>
            <button type="submit" class="btn-primary mt-4"><i class="fas fa-save mr-2"></i>Guardar cambios</button>
        </form>
    </div>
</div>
@endsection
