<?php

use Illuminate\Support\Facades\Route;
use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

// ============================================
// TIENDA PÚBLICA (sin autenticación)
// ============================================

// Catálogo público de la sucursal
Route::get('/tienda/{slug}', function ($slug) {
    $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

    config(['database.connections.tenant.database' => $sucursal->base_datos]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $categorias = DB::connection('tenant')->table('categorias')->orderBy('nombre')->get();

    $productos = DB::connection('tenant')
        ->table('productos')
        ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
        ->select('productos.*', 'categorias.nombre as categoria_nombre')
        ->where('productos.stock_actual', '>', 0)
        ->where('productos.activo', true)
        ->orderBy('categorias.nombre')
        ->orderBy('productos.nombre')
        ->get();

    return view('tienda.catalogo', compact('sucursal', 'categorias', 'productos'));
})->name('tienda.catalogo');

// ============================================
// RUTAS DE AUTENTICACIÓN (Manuales)
// ============================================

// Mostrar formulario de login
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// Procesar login
Route::post('/login', function () {
    $credentials = request()->only('email', 'password');
    
    if (Auth::attempt($credentials)) {
        request()->session()->regenerate();
        return redirect('/admin');
    }
    
    return back()->withErrors([
        'email' => 'Las credenciales no coinciden.',
    ]);
})->name('login.post');

// Cerrar sesión
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// ============================================
// LANDING PÚBLICA (raíz del dominio)
// ============================================
Route::get('/', function () {
    $sucursal  = Sucursal::where('activa', true)->orderBy('id')->first();
    $productos = collect();
    $categorias = collect();

    if ($sucursal) {
        try {
            config(['database.connections.tenant.database' => $sucursal->base_datos]);
            DB::purge('tenant'); DB::reconnect('tenant');

            $categorias = DB::connection('tenant')->table('categorias')->orderBy('nombre')->get();

            $productos = DB::connection('tenant')->table('productos')
                ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
                ->select('productos.*', 'categorias.nombre as categoria_nombre')
                ->where('productos.stock_actual', '>', 0)
                ->where('productos.activo', true)
                ->orderBy('productos.id', 'desc')
                ->limit(12)
                ->get();
        } catch (\Throwable $e) {
            // Si falla la conexión tenant, la landing igual se muestra sin catálogo
        }
    }

    return view('landing.index', compact('sucursal', 'productos', 'categorias'));
})->name('landing');

// Generador de URL de WhatsApp con validación de teléfono boliviano
Route::post('/pedido/whatsapp', function () {
    $datos = request()->validate([
        'nombre'    => 'required|string|max:100',
        'telefono'  => ['required', 'string', 'regex:/^[67]\d{7}$/'],
        'tipo'      => 'required|in:recoger,llevar',
        'direccion' => 'nullable|string|max:255|required_if:tipo,llevar',
        'pedido'    => 'required|string|max:1000',
    ], [
        'telefono.regex'        => 'Debes ingresar un número celular válido de Bolivia: 8 dígitos que empiecen con 6 o 7.',
        'direccion.required_if' => 'La dirección es obligatoria cuando el pedido es a domicilio.',
        'pedido.required'       => 'Debes describir tu pedido.',
    ]);

    $tipoTexto = $datos['tipo'] === 'recoger'
        ? 'Recoger en tienda'
        : 'Llevar a domicilio';

    $msg  = "Hola Elixir Dorado, quiero hacer un pedido.\n";
    $msg .= "------------------------------\n";
    $msg .= "Cliente: {$datos['nombre']}\n";
    $msg .= "Telefono: +591 {$datos['telefono']}\n";
    $msg .= "Tipo: {$tipoTexto}\n";
    if ($datos['tipo'] === 'llevar') {
        $msg .= "Direccion: {$datos['direccion']}\n";
    }
    $msg .= "------------------------------\n";
    $msg .= "Pedido:\n{$datos['pedido']}\n";

    // Asegurar UTF-8 limpio antes de codificar (evita caracteres rotos en WhatsApp)
    $msg = mb_convert_encoding($msg, 'UTF-8', 'UTF-8');

    $whatsappUrl = 'https://wa.me/59168289548?text=' . rawurlencode($msg);

    return response()->json(['ok' => true, 'url' => $whatsappUrl]);
});

// ============================================
// PANEL DE ADMINISTRACIÓN (protegido — solo super_admin/admin)
// ============================================
Route::prefix('admin')->middleware(['auth', 'role:super_admin,admin'])->group(function () {

    // Setup wizard — primera instalación
    Route::get('/setup', function () {
        if (Sucursal::count() > 0) return redirect('/admin');
        return view('admin.setup', ['errors' => session()->get('errors', new \Illuminate\Support\MessageBag)]);
    });

    Route::post('/setup', function () {
        if (Sucursal::count() > 0) return redirect('/admin');

        $data = request()->validate([
            'nombre'                => 'required|string|max:100',
            'slug'                  => 'required|string|max:50|unique:sucursales|regex:/^[a-z0-9-]+$/',
            'telefono'              => 'nullable|string|max:30',
            'direccion'             => 'nullable|string|max:255',
            'admin_nombre'          => 'required|string|max:100',
            'admin_email'           => 'required|email|max:255|unique:users,email',
            'admin_password'        => 'required|min:8',
            'admin_password_confirm'=> 'required|same:admin_password',
        ], [
            'slug.regex'            => 'El identificador solo puede tener letras minúsculas, números y guiones.',
            'slug.unique'           => 'Ya existe una sucursal con ese identificador.',
            'admin_email.unique'    => 'Ese correo ya está registrado.',
            'admin_password.min'    => 'La contraseña debe tener al menos 8 caracteres.',
            'admin_password_confirm.same' => 'Las contraseñas no coinciden.',
        ]);

        // Crear sucursal y admin usando el modelo existente
        try {
            $sucursal = Sucursal::crearSucursal([
                'nombre'         => $data['nombre'],
                'slug'           => $data['slug'],
                'telefono'       => $data['telefono'] ?? null,
                'direccion'      => $data['direccion'] ?? null,
                'email'          => $data['admin_email'],
                'admin_nombre'   => $data['admin_nombre'],
                'admin_email'    => $data['admin_email'],
                'admin_password' => $data['admin_password'],
            ]);
            return redirect('/admin')->with('success', "¡Sistema configurado! Bienvenido a Elixirdorado, {$data['admin_nombre']}.");
        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'Error al crear la sucursal: ' . $e->getMessage()])->withInput();
        }
    });

    Route::get('/', function () {
        // Si no hay sucursales, mostrar wizard de primera instalación
        if (Sucursal::count() === 0) return redirect('/admin/setup');

        $sucursales = Sucursal::all();

        // Recopilar stats reales de cada sucursal
        $stats = [];
        foreach ($sucursales as $s) {
            try {
                config(['database.connections.tenant.database' => $s->base_datos]);
                DB::purge('tenant'); DB::reconnect('tenant');

                $stats[$s->id] = [
                    'ventas_hoy'      => DB::connection('tenant')->table('ventas')->whereDate('fecha_venta', today())->where('estado','!=','cancelada')->sum('total'),
                    'ventas_mes'      => DB::connection('tenant')->table('ventas')->whereMonth('fecha_venta', now()->month)->whereYear('fecha_venta', now()->year)->where('estado','!=','cancelada')->sum('total'),
                    'total_productos' => DB::connection('tenant')->table('productos')->count(),
                    'bajo_stock'      => DB::connection('tenant')->table('productos')->whereColumn('stock_actual','<=','stock_minimo')->count(),
                    'total_clientes'  => Schema::connection('tenant')->hasTable('clientes')
                        ? DB::connection('tenant')->table('clientes')->count() : 0,
                    'ventas_count'    => DB::connection('tenant')->table('ventas')->whereDate('fecha_venta', today())->where('estado','!=','cancelada')->count(),
                ];
            } catch (\Exception $e) {
                $stats[$s->id] = ['error' => true, 'ventas_hoy'=>0,'ventas_mes'=>0,'total_productos'=>0,'bajo_stock'=>0,'total_clientes'=>0,'ventas_count'=>0];
            }
        }

        $totalHoyGlobal = collect($stats)->sum('ventas_hoy');
        $totalMesGlobal = collect($stats)->sum('ventas_mes');

        return view('admin.dashboard', compact('sucursales', 'stats', 'totalHoyGlobal', 'totalMesGlobal'));
    });
    
    Route::post('/sucursales', function () {
        try {
            $sucursal = Sucursal::crearSucursal(request()->validate([
                'nombre'         => 'required|string|max:100',
                'slug'           => 'required|string|max:50|unique:sucursales|regex:/^[a-z0-9_-]+$/',
                'direccion'      => 'nullable|string|max:255',
                'telefono'       => 'nullable|string|max:30',
                'email'          => 'nullable|email|max:255',
                'admin_nombre'   => 'required|string|max:100',
                'admin_email'    => 'required|email|max:255',
                'admin_password' => 'required|min:8',
            ], [
                'slug.regex' => 'El identificador solo puede tener minúsculas, números, "_" y "-".',
                'admin_password.min' => 'La contraseña del administrador debe tener al menos 8 caracteres.',
            ]));
            return redirect('/admin')->with('success', "Sucursal {$sucursal->nombre} creada");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['admin_password' => $e->getMessage()])->withInput();
        } catch (\Throwable $e) {
            return back()->withErrors(['general' => 'No se pudo crear la sucursal: ' . $e->getMessage()])->withInput();
        }
    });
    
    // Reportes
    Route::get('/reportes', function () {
        $sucursales = Sucursal::where('activa', true)->get();
        $reportes = [];
        
        foreach ($sucursales as $sucursal) {
            try {
                config(['database.connections.tenant.database' => $sucursal->base_datos]);
                DB::purge('tenant');
                DB::reconnect('tenant');
                
                $ventasHoy = DB::connection('tenant')->table('ventas')
                    ->whereDate('fecha_venta', today())
                    ->sum('total');
                
                $ventasMes = DB::connection('tenant')->table('ventas')
                    ->whereMonth('fecha_venta', now()->month)
                    ->sum('total');
                
                $totalVentas = DB::connection('tenant')->table('ventas')->sum('total');
                $cantidadVentas = DB::connection('tenant')->table('ventas')->count();
                
                $productosTop = DB::connection('tenant')->table('detalle_ventas')
                    ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
                    ->select('productos.nombre', DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'))
                    ->groupBy('productos.id', 'productos.nombre')
                    ->orderBy('total_vendido', 'desc')
                    ->limit(5)
                    ->get();
                
                $reportes[$sucursal->slug] = [
                    'sucursal' => $sucursal,
                    'ventas_hoy' => $ventasHoy,
                    'ventas_mes' => $ventasMes,
                    'total_ventas' => $totalVentas,
                    'cantidad_ventas' => $cantidadVentas,
                    'productos_top' => $productosTop
                ];
            } catch (\Exception $e) {
                $reportes[$sucursal->slug] = [
                    'sucursal' => $sucursal,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $totalGeneral = collect($reportes)->sum('total_ventas');
        $ventasHoyGeneral = collect($reportes)->sum('ventas_hoy');
        $ventasMesGeneral = collect($reportes)->sum('ventas_mes');
        
        return view('admin.reportes', compact('reportes', 'totalGeneral', 'ventasHoyGeneral', 'ventasMesGeneral'));
    })->name('admin.reportes');
    
    Route::get('/reportes/{slug}/ventas', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        
        $ventas = DB::connection('tenant')->table('ventas')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.ventas-sucursal', compact('sucursal', 'ventas'));
    })->name('admin.ventas.sucursal');
});

// ============================================
// PANEL DE CADA SUCURSAL (protegido)
// ============================================
Route::middleware('auth')->group(function () {

    // Inventario
    Route::get('/{slug}/inventario', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');
        $productos  = DB::connection('tenant')->table('productos')->orderBy('nombre')->get();
        $categorias = DB::connection('tenant')->table('categorias')->get();
        return view('sucursal.inventario', compact('sucursal', 'productos', 'categorias'));
    })->name('sucursal.inventario');

    // Ajuste de stock
    Route::post('/{slug}/inventario/ajuste', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');

        $datos = request()->validate([
            'producto_id' => 'required|integer',
            'tipo_ajuste' => 'required|in:entrada,salida,correccion',
            'cantidad'    => 'required|integer|min:1',
            'motivo'      => 'nullable|string|max:255',
        ]);

        $prod = DB::connection('tenant')->table('productos')->where('id', $datos['producto_id'])->first();
        if (!$prod) return back()->with('error', 'Producto no encontrado');

        if ($datos['tipo_ajuste'] === 'entrada') {
            DB::connection('tenant')->table('productos')->where('id', $prod->id)
                ->increment('stock_actual', $datos['cantidad']);
            $msg = "Stock aumentado en {$datos['cantidad']} unidades";
        } elseif ($datos['tipo_ajuste'] === 'salida') {
            if ($prod->stock_actual < $datos['cantidad'])
                return back()->with('error', 'No hay suficiente stock para la salida');
            DB::connection('tenant')->table('productos')->where('id', $prod->id)
                ->decrement('stock_actual', $datos['cantidad']);
            $msg = "Stock reducido en {$datos['cantidad']} unidades";
        } else {
            DB::connection('tenant')->table('productos')->where('id', $prod->id)
                ->update(['stock_actual' => $datos['cantidad'], 'updated_at' => now()]);
            $msg = "Stock corregido a {$datos['cantidad']} unidades";
        }

        return redirect("/{$slug}/inventario")->with('success', $msg);
    });

    // Editar producto (PUT)
    Route::put('/{slug}/productos/editar', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');

        $datos = request()->validate([
            'id'            => 'required|integer',
            'nombre'        => 'required|string|max:255',
            'codigo_barras' => 'nullable|string|max:100',
            'descripcion'   => 'nullable|string',
            'categoria_id'  => 'nullable|integer',
            'precio_compra' => 'nullable|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock_minimo'  => 'nullable|integer|min:0',
            'imagen'        => 'nullable|image|max:2048',
        ]);

        $id = $datos['id'];
        unset($datos['id']);

        if (request()->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            $prod = DB::connection('tenant')->table('productos')->where('id', $id)->first();
            if ($prod && $prod->imagen) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($prod->imagen);
            }
            $datos['imagen'] = request()->file('imagen')
                ->store("productos/{$slug}", 'public');
        } else {
            unset($datos['imagen']);
        }

        $datos['updated_at'] = now();
        DB::connection('tenant')->table('productos')->where('id', $id)->update($datos);
        return redirect("/{$slug}/productos")->with('success', 'Producto actualizado correctamente');
    });

    // Clientes
    Route::get('/{slug}/clientes', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');
        $clientes = DB::connection('tenant')->table('clientes')->orderBy('nombre')->get();
        return view('sucursal.clientes', compact('sucursal', 'clientes'));
    })->name('sucursal.clientes');

    Route::post('/{slug}/clientes', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');
        $datos = request()->validate([
            'nombre'         => 'required|string|max:255',
            'telefono'       => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:255',
            'rfc_nit'        => 'nullable|string|max:50',
            'direccion'      => 'nullable|string|max:500',
            'limite_credito' => 'nullable|numeric|min:0',
        ]);
        DB::connection('tenant')->table('clientes')->insert(array_merge($datos, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return redirect("/{$slug}/clientes")->with('success', 'Cliente registrado correctamente');
    });

    // Actualizar cliente (PUT)
    Route::put('/{slug}/clientes/{id}', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');
        $datos = request()->validate([
            'nombre'         => 'required|string|max:255',
            'telefono'       => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:255',
            'rfc_nit'        => 'nullable|string|max:50',
            'direccion'      => 'nullable|string|max:500',
            'limite_credito' => 'nullable|numeric|min:0',
        ]);
        DB::connection('tenant')->table('clientes')->where('id', $id)->update(array_merge($datos, [
            'updated_at' => now(),
        ]));
        return redirect("/{$slug}/clientes")->with('success', 'Cliente actualizado correctamente');
    });

    // Eliminar cliente
    Route::delete('/{slug}/clientes/{id}', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');
        DB::connection('tenant')->table('clientes')->where('id', $id)->delete();
        return redirect("/{$slug}/clientes")->with('success', 'Cliente eliminado');
    });

    // Reportes de sucursal
    Route::get('/{slug}/reportes', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');

        // ── Ventas por día (última semana) ─────────────────────
        $ventasSemana = DB::connection('tenant')->table('ventas')
            ->selectRaw('DATE(fecha_venta) as dia, SUM(total) as total, COUNT(*) as cantidad')
            ->where('fecha_venta', '>=', now()->subDays(6)->startOfDay())
            ->where('estado', '!=', 'cancelada')
            ->groupBy('dia')->orderBy('dia')->get();

        // ── Ventas por método de pago (semana) ─────────────────
        $ventasPago = DB::connection('tenant')->table('ventas')
            ->selectRaw('metodo_pago, SUM(total) as total, COUNT(*) as cantidad')
            ->where('fecha_venta', '>=', now()->subDays(6)->startOfDay())
            ->where('estado', '!=', 'cancelada')
            ->groupBy('metodo_pago')->get();

        // ── Productos más vendidos ──────────────────────────────
        $productosTop = DB::connection('tenant')->table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->selectRaw('productos.nombre, SUM(detalle_ventas.cantidad) as total_uds, SUM(detalle_ventas.subtotal) as total_bs')
            ->where('ventas.fecha_venta', '>=', now()->subDays(29)->startOfDay())
            ->where('ventas.estado', '!=', 'cancelada')
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_uds')->limit(10)->get();

        // ── Resumen mensual ────────────────────────────────────
        $ventasMes = DB::connection('tenant')->table('ventas')
            ->whereMonth('fecha_venta', now()->month)->whereYear('fecha_venta', now()->year)
            ->where('estado', '!=', 'cancelada');
        $totalMes      = $ventasMes->sum('total');
        $cantidadMes   = $ventasMes->count();

        // ── Resumen hoy ────────────────────────────────────────
        $totalHoy    = DB::connection('tenant')->table('ventas')
            ->whereDate('fecha_venta', today())->where('estado','!=','cancelada')->sum('total');
        $cantidadHoy = DB::connection('tenant')->table('ventas')
            ->whereDate('fecha_venta', today())->where('estado','!=','cancelada')->count();

        // ── Ganancia por categoría ─────────────────────────────
        $gananciaCat = DB::connection('tenant')->table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->selectRaw('COALESCE(categorias.nombre,"Sin Departamento") as categoria,
                         SUM(detalle_ventas.subtotal - (detalle_ventas.cantidad * productos.precio_compra)) as ganancia')
            ->where('ventas.fecha_venta', '>=', now()->subDays(6)->startOfDay())
            ->where('ventas.estado', '!=', 'cancelada')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('ganancia')->get();

        return view('sucursal.reportes', compact(
            'sucursal', 'ventasSemana', 'ventasPago', 'productosTop',
            'totalMes', 'cantidadMes', 'totalHoy', 'cantidadHoy', 'gananciaCat'
        ));
    })->name('sucursal.reportes');

    // Compras (placeholder)
    Route::get('/{slug}/compras', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        return view('sucursal.compras', compact('sucursal'));
    })->name('sucursal.compras');

    // Créditos (placeholder)
    Route::get('/{slug}/creditos', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        return view('sucursal.creditos', compact('sucursal'));
    })->name('sucursal.creditos');

    // Facturas (placeholder)
    Route::get('/{slug}/facturas', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');
        $ventas = DB::connection('tenant')->table('ventas')->orderBy('created_at','desc')->get();
        return view('sucursal.facturas', compact('sucursal', 'ventas'));
    })->name('sucursal.facturas');

    // Corte de caja (placeholder)
    Route::get('/{slug}/corte', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');
        $ventasHoy = DB::connection('tenant')->table('ventas')->whereDate('fecha_venta', today())->sum('total');
        $ventasPorMetodo = DB::connection('tenant')->table('ventas')->whereDate('fecha_venta', today())->select('metodo_pago', DB::raw('SUM(total) as total'))->groupBy('metodo_pago')->get();
        return view('sucursal.corte', compact('sucursal', 'ventasHoy', 'ventasPorMetodo'));
    })->name('sucursal.corte');

    // Configuración
    Route::get('/{slug}/configuracion', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        return view('sucursal.configuracion', compact('sucursal'));
    })->name('sucursal.configuracion');

    // ── Helper para ejecutar el generador Excel ───────────────────────────────
    // (función local, no es una ruta)

    // ── Exportar Ventas a Excel ────────────────────────────────────────────────
    Route::get('/{slug}/ventas/excel', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');

        $ventas = DB::connection('tenant')->table('ventas')
            ->orderBy('fecha_venta', 'desc')->get();

        // Rutas con / hacia adelante (funciona en Windows y Linux)
        $tmpFile    = str_replace('\\', '/', sys_get_temp_dir()) . '/ventas_' . $slug . '_' . time() . '.xlsx';
        $scriptPath = str_replace('\\', '/', base_path('scripts/generar_excel.py'));

        $items = $ventas->map(fn($v) => [
            'fecha'       => \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y H:i'),
            'folio'       => $v->folio,
            'metodo_pago' => $v->metodo_pago,
            'subtotal'    => (float)$v->subtotal,
            'iva'         => (float)($v->iva ?? 0),
            'total'       => (float)$v->total,
            'estado'      => $v->estado,
        ])->toArray();

        $payload = json_encode([
            'tipo'     => 'ventas',
            'sucursal' => $sucursal->nombre,
            'fecha'    => now()->format('d/m/Y H:i'),
            'output'   => $tmpFile,
            'items'    => $items,
        ], JSON_UNESCAPED_UNICODE);

        // Buscar Python: intenta 'python', luego 'python3', luego ruta típica de Windows
        $pythonCandidates = PHP_OS_FAMILY === 'Windows'
            ? ['python', 'py', 'C:/Python312/python.exe', 'C:/Python311/python.exe',
               'C:/Python310/python.exe', 'C:/Users/' . get_current_user() . '/AppData/Local/Programs/Python/Python312/python.exe']
            : ['python3', 'python'];

        $pythonBin = 'python';
        foreach ($pythonCandidates as $candidate) {
            $test = new \Symfony\Component\Process\Process([$candidate, '--version']);
            $test->run();
            if ($test->isSuccessful()) { $pythonBin = $candidate; break; }
        }

        $process = new \Symfony\Component\Process\Process([$pythonBin, $scriptPath]);
        $process->setInput($payload);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful() || !file_exists($tmpFile)) {
            $err = $process->getErrorOutput() ?: $process->getOutput();
            return back()->with('error', 'Error al generar Excel: ' . $err);
        }

        return response()->download($tmpFile, 'ventas_' . $slug . '_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    });

    // ── Exportar Inventario a Excel ────────────────────────────────────────────
    Route::get('/{slug}/inventario/excel', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');

        $productos  = DB::connection('tenant')->table('productos')->orderBy('nombre')->get();
        $categorias = DB::connection('tenant')->table('categorias')->get()->keyBy('id');

        $tmpFile    = str_replace('\\', '/', sys_get_temp_dir()) . '/inventario_' . $slug . '_' . time() . '.xlsx';
        $scriptPath = str_replace('\\', '/', base_path('scripts/generar_excel.py'));

        $items = $productos->map(fn($p) => [
            'codigo_barras' => $p->codigo_barras ?? '',
            'nombre'        => $p->nombre,
            'categoria'     => $categorias->has($p->categoria_id)
                ? $categorias[$p->categoria_id]->nombre : 'Sin categoría',
            'precio_venta'  => (float)$p->precio_venta,
            'stock_actual'  => (int)$p->stock_actual,
            'stock_minimo'  => (int)($p->stock_minimo ?? 0),
        ])->toArray();

        $payload = json_encode([
            'tipo'     => 'inventario',
            'sucursal' => $sucursal->nombre,
            'fecha'    => now()->format('d/m/Y H:i'),
            'output'   => $tmpFile,
            'items'    => $items,
        ], JSON_UNESCAPED_UNICODE);

        $pythonCandidates = PHP_OS_FAMILY === 'Windows'
            ? ['python', 'py', 'C:/Python312/python.exe', 'C:/Python311/python.exe',
               'C:/Python310/python.exe', 'C:/Users/' . get_current_user() . '/AppData/Local/Programs/Python/Python312/python.exe']
            : ['python3', 'python'];

        $pythonBin = 'python';
        foreach ($pythonCandidates as $candidate) {
            $test = new \Symfony\Component\Process\Process([$candidate, '--version']);
            $test->run();
            if ($test->isSuccessful()) { $pythonBin = $candidate; break; }
        }

        $process = new \Symfony\Component\Process\Process([$pythonBin, $scriptPath]);
        $process->setInput($payload);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful() || !file_exists($tmpFile)) {
            $err = $process->getErrorOutput() ?: $process->getOutput();
            return back()->with('error', 'Error al generar Excel: ' . $err);
        }

        return response()->download($tmpFile, 'inventario_' . $slug . '_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    });

    // ── Detalle de venta (JSON para modal) ────────────────────────────────
    Route::get('/{slug}/ventas/{id}/detalle', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');

        $venta = DB::connection('tenant')->table('ventas')->where('id', $id)->first();
        if (!$venta) return response()->json(['error' => 'No encontrada'], 404);

        $items = DB::connection('tenant')->table('detalle_ventas')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->select('productos.nombre', 'productos.codigo_barras',
                     'detalle_ventas.cantidad', 'detalle_ventas.precio_unitario', 'detalle_ventas.subtotal')
            ->where('detalle_ventas.venta_id', $id)
            ->get();

        return response()->json(['venta' => $venta, 'items' => $items]);
    });

    // ── Cancelar venta ─────────────────────────────────────────────────────
    Route::post('/{slug}/ventas/{id}/cancelar', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');

        $venta = DB::connection('tenant')->table('ventas')->where('id', $id)->first();
        if (!$venta) return response()->json(['error' => 'No encontrada'], 404);
        if ($venta->estado === 'cancelada') return response()->json(['error' => 'Ya cancelada'], 400);

        // Restaurar stock + marcar cancelada en transacción atómica
        try {
            DB::connection('tenant')->transaction(function () use ($id) {
                $items = DB::connection('tenant')->table('detalle_ventas')->where('venta_id', $id)->get();
                foreach ($items as $item) {
                    DB::connection('tenant')->table('productos')
                        ->where('id', $item->producto_id)
                        ->increment('stock_actual', $item->cantidad);
                }

                DB::connection('tenant')->table('ventas')->where('id', $id)->update([
                    'estado'     => 'cancelada',
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => 'No se pudo cancelar: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true]);
    });

    // Manual de ayuda del cajero
    Route::get('/{slug}/ayuda', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        return view('sucursal.ayuda', compact('sucursal'));
    })->name('sucursal.ayuda');

    Route::get('/{slug}', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        config(['database.default' => 'tenant']);
        
        $productos = DB::connection('tenant')->table('productos')->get();
        $ventasHoy = DB::connection('tenant')->table('ventas')
            ->whereDate('fecha_venta', today())
            ->sum('total');
        $totalProductos = DB::connection('tenant')->table('productos')->count();
        $productosBajoStock = DB::connection('tenant')->table('productos')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->count();
        
        return view('sucursal.dashboard', compact('sucursal', 'productos', 'ventasHoy', 'totalProductos', 'productosBajoStock'));
    })->name('sucursal.dashboard');
    
    Route::get('/{slug}/productos', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        config(['database.default' => 'tenant']);
        
        $productos = DB::connection('tenant')->table('productos')->get();
        $categorias = DB::connection('tenant')->table('categorias')->get();
        
        return view('sucursal.productos', compact('sucursal', 'productos', 'categorias'));
    })->name('sucursal.productos');
    
    // Activar/desactivar producto
    Route::patch('/{slug}/productos/{id}/toggle', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');

        $prod = DB::connection('tenant')->table('productos')->where('id', $id)->first();
        if (!$prod) return response()->json(['error' => 'No encontrado'], 404);

        $nuevoEstado = !$prod->activo;
        DB::connection('tenant')->table('productos')->where('id', $id)
            ->update(['activo' => $nuevoEstado, 'updated_at' => now()]);

        return response()->json([
            'activo' => $nuevoEstado,
            'label'  => $nuevoEstado ? 'Activo' : 'Inactivo',
        ]);
    });

    // Eliminar producto
    Route::delete('/{slug}/productos/{id}', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant'); DB::reconnect('tenant');

        $prod = DB::connection('tenant')->table('productos')->where('id', $id)->first();
        if (!$prod) return redirect("/{$slug}/productos")->with('error', 'Producto no encontrado');

        // Verificar si el producto tiene ventas registradas
        $tieneVentas = DB::connection('tenant')->table('detalle_ventas')
            ->where('producto_id', $id)->exists();

        if ($tieneVentas) {
            return redirect("/{$slug}/productos")->with('error',
                "No se puede eliminar \"{$prod->nombre}\" porque tiene ventas registradas. Puedes dejarlo sin stock si ya no lo usas.");
        }

        if ($prod->imagen) {
            Storage::disk('public')->delete($prod->imagen);
        }

        DB::connection('tenant')->table('productos')->where('id', $id)->delete();
        return redirect("/{$slug}/productos")->with('success', "Producto \"{$prod->nombre}\" eliminado correctamente");
    });

    Route::post('/{slug}/productos', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        config(['database.default' => 'tenant']);

        $datos = request()->validate([
            'nombre'        => 'required|string',
            'codigo_barras' => 'nullable|string',
            'descripcion'   => 'nullable|string',
            'precio_compra' => 'required|numeric',
            'precio_venta'  => 'required|numeric',
            'stock_actual'  => 'required|integer',
            'stock_minimo'  => 'required|integer',
            'categoria_id'  => 'nullable|integer',
            'imagen'        => 'required|image|max:2048',
        ], [
            'imagen.required' => 'La imagen del producto es obligatoria.',
            'imagen.image'    => 'El archivo debe ser una imagen (JPG, PNG o WEBP).',
            'imagen.max'      => 'La imagen no debe superar los 2MB.',
        ]);

        $datos['imagen'] = request()->file('imagen')->store("productos/{$slug}", 'public');
        $datos['activo']     = true;
        $datos['created_at'] = now();
        $datos['updated_at'] = now();

        DB::connection('tenant')->table('productos')->insert($datos);

        return redirect("/{$slug}/productos")->with('success', 'Producto creado correctamente');
    });
    
    Route::get('/{slug}/ventas', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        config(['database.default' => 'tenant']);
        
        $ventas = DB::connection('tenant')->table('ventas')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('sucursal.ventas', compact('sucursal', 'ventas'));
    })->name('sucursal.ventas');
    
    // Punto de Venta
    Route::get('/{slug}/pos', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        config(['database.default' => 'tenant']);
        
        $productos = DB::connection('tenant')->table('productos')
            ->where('stock_actual', '>', 0)
            ->where('activo', true)
            ->get();

        return view('sucursal.pos', compact('sucursal', 'productos'));
    })->name('sucursal.pos');
    
    Route::post('/{slug}/pos/venta', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        
        config(['database.connections.tenant.database' => $sucursal->base_datos]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        config(['database.default' => 'tenant']);
        
        $datos = request()->validate([
            'items' => 'required|array',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio' => 'required|numeric',
            'total' => 'required|numeric',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
        ]);
        
        $folio    = 'VENTA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $ivaRate  = (float) config('negocio.iva', 0.13);
        $subtotal = $datos['total'] / (1 + $ivaRate);
        $iva      = $datos['total'] - $subtotal;

        try {
            $ventaId = DB::connection('tenant')->transaction(function () use ($datos, $folio, $subtotal, $iva) {
                // Bloquear filas de productos involucrados para evitar oversell
                $ids = collect($datos['items'])->pluck('producto_id')->all();
                $stocks = DB::connection('tenant')->table('productos')
                    ->whereIn('id', $ids)
                    ->lockForUpdate()
                    ->pluck('stock_actual', 'id');

                foreach ($datos['items'] as $item) {
                    if (($stocks[$item['producto_id']] ?? 0) < $item['cantidad']) {
                        throw new \RuntimeException('Stock insuficiente para producto ID ' . $item['producto_id']);
                    }
                }

                $ventaId = DB::connection('tenant')->table('ventas')->insertGetId([
                    'folio'       => $folio,
                    'usuario_id'  => auth()->id(),
                    'subtotal'    => $subtotal,
                    'iva'         => $iva,
                    'total'       => $datos['total'],
                    'metodo_pago' => $datos['metodo_pago'],
                    'estado'      => 'completada',
                    'fecha_venta' => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                foreach ($datos['items'] as $item) {
                    DB::connection('tenant')->table('detalle_ventas')->insert([
                        'venta_id'        => $ventaId,
                        'producto_id'     => $item['producto_id'],
                        'cantidad'        => $item['cantidad'],
                        'precio_unitario' => $item['precio'],
                        'subtotal'        => $item['cantidad'] * $item['precio'],
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    DB::connection('tenant')->table('productos')
                        ->where('id', $item['producto_id'])
                        ->decrement('stock_actual', $item['cantidad']);
                }

                return $ventaId;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la venta: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Venta registrada',
            'folio'   => $folio,
            'total'   => $datos['total'],
        ]);
    });
});