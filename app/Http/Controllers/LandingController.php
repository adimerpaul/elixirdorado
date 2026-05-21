<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function index()
    {
        $sucursal   = Sucursal::where('activa', true)->orderBy('id')->first();
        $productos  = collect();
        $categorias = collect();

        if ($sucursal) {
            $categorias = DB::table('categorias')
                ->where('sucursal_id', $sucursal->id)
                ->orderBy('nombre')
                ->get();

            $productos = DB::table('productos')
                ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
                ->select('productos.*', 'categorias.nombre as categoria_nombre')
                ->where('productos.sucursal_id', $sucursal->id)
                ->where('productos.stock_actual', '>', 0)
                ->where('productos.activo', true)
                ->whereNull('productos.deleted_at')
                ->orderBy('categorias.nombre')
                ->orderBy('productos.nombre')
                ->get();
        }

        $whatsapp = Configuracion::get('whatsapp', '59168289548');

        return view('landing.index', compact('sucursal', 'productos', 'categorias', 'whatsapp'));
    }
}
