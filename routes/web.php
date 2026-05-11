<?php

use Illuminate\Support\Facades\Route;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

// ============================================
// TIENDA PÚBLICA (sin autenticación)
// ============================================

Route::get('/tienda/{slug}', function ($slug) {
    $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

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

    return view('tienda.catalogo', compact('sucursal', 'categorias', 'productos'));
})->name('tienda.catalogo');

// ============================================
// RUTAS DE AUTENTICACIÓN (Manuales)
// ============================================

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    $login    = trim(request()->input('login', ''));
    $password = request()->input('password', '');

    if (!$login || !$password) {
        return back()->withErrors(['login' => 'Ingresa tu usuario y contraseña.'])->withInput();
    }

    $user = \App\Models\User::where('email', $login)
        ->orWhere('nickname', $login)
        ->first();

    if ($user && Hash::check($password, $user->password)) {
        Auth::login($user);
        request()->session()->regenerate();
        return redirect('/admin');
    }

    return back()->withErrors(['login' => 'Usuario o contraseña incorrectos.'])->withInput();
})->name('login.post');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// ============================================
// LANDING PÚBLICA
// ============================================
Route::get('/', function () {
    $sucursal  = Sucursal::where('activa', true)->orderBy('id')->first();
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
            ->orderBy('productos.id', 'desc')
            ->limit(12)
            ->get();
    }

    return view('landing.index', compact('sucursal', 'productos', 'categorias'));
})->name('landing');

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

    $tipoTexto = $datos['tipo'] === 'recoger' ? 'Recoger en tienda' : 'Llevar a domicilio';

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

    $msg = mb_convert_encoding($msg, 'UTF-8', 'UTF-8');
    $whatsappUrl = 'https://wa.me/59168289548?text=' . rawurlencode($msg);

    return response()->json(['ok' => true, 'url' => $whatsappUrl]);
});

// ============================================
// PANEL DE ADMINISTRACIÓN
// ============================================

Route::prefix('admin')->middleware(['auth', 'role:super_admin,admin'])->group(function () {
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

        try {
            $sucursal = Sucursal::create([
                'nombre'    => $data['nombre'],
                'slug'      => $data['slug'],
                'telefono'  => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'email'     => $data['admin_email'],
                'activa'    => true,
            ]);

            \App\Models\User::create([
                'name'        => $data['admin_nombre'],
                'email'       => $data['admin_email'],
                'password'    => Hash::make($data['admin_password']),
                'rol'         => 'admin',
                'sucursal_id' => $sucursal->id,
            ]);

            return redirect('/admin')->with('success', "¡Sistema configurado! Bienvenido a Elixirdorado.");
        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'Error: ' . $e->getMessage()])->withInput();
        }
    });
});

Route::middleware(['auth', 'role:super_admin,admin'])
    ->get('/admin/{any?}', fn () => view('admin.spa'))
    ->where('any', '.*');

// ============================================
// PANEL DE CADA SUCURSAL
// ============================================
Route::middleware('auth')->group(function () {

    // Inventario
    Route::get('/{slug}/inventario', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $productos  = DB::table('productos')->where('sucursal_id', $sucursal->id)->orderBy('nombre')->get();
        $categorias = DB::table('categorias')->where('sucursal_id', $sucursal->id)->get();
        return view('sucursal.inventario', compact('sucursal', 'productos', 'categorias'));
    })->name('sucursal.inventario');

    // Ajuste de stock
    Route::post('/{slug}/inventario/ajuste', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

        $datos = request()->validate([
            'producto_id' => 'required|integer',
            'tipo_ajuste' => 'required|in:entrada,salida,correccion',
            'cantidad'    => 'required|integer|min:1',
            'motivo'      => 'nullable|string|max:255',
        ]);

        $prod = DB::table('productos')
            ->where('sucursal_id', $sucursal->id)
            ->where('id', $datos['producto_id'])
            ->first();

        if (!$prod) return back()->with('error', 'Producto no encontrado');

        if ($datos['tipo_ajuste'] === 'entrada') {
            DB::table('productos')->where('id', $prod->id)->increment('stock_actual', $datos['cantidad']);
            $msg = "Stock aumentado en {$datos['cantidad']} unidades";
        } elseif ($datos['tipo_ajuste'] === 'salida') {
            if ($prod->stock_actual < $datos['cantidad'])
                return back()->with('error', 'No hay suficiente stock para la salida');
            DB::table('productos')->where('id', $prod->id)->decrement('stock_actual', $datos['cantidad']);
            $msg = "Stock reducido en {$datos['cantidad']} unidades";
        } else {
            DB::table('productos')->where('id', $prod->id)
                ->update(['stock_actual' => $datos['cantidad'], 'updated_at' => now()]);
            $msg = "Stock corregido a {$datos['cantidad']} unidades";
        }

        return redirect("/{$slug}/inventario")->with('success', $msg);
    });

    // Editar producto
    Route::put('/{slug}/productos/editar', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

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
            $prod = DB::table('productos')
                ->where('sucursal_id', $sucursal->id)
                ->where('id', $id)
                ->first();
            if ($prod && $prod->imagen) {
                Storage::disk('public')->delete($prod->imagen);
            }
            $datos['imagen'] = request()->file('imagen')->store("productos/{$slug}", 'public');
        } else {
            unset($datos['imagen']);
        }

        $datos['updated_at'] = now();
        DB::table('productos')->where('sucursal_id', $sucursal->id)->where('id', $id)->update($datos);
        return redirect("/{$slug}/productos")->with('success', 'Producto actualizado correctamente');
    });

    // Clientes
    Route::get('/{slug}/clientes', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $clientes = DB::table('clientes')->where('sucursal_id', $sucursal->id)->orderBy('nombre')->get();
        return view('sucursal.clientes', compact('sucursal', 'clientes'));
    })->name('sucursal.clientes');

    Route::post('/{slug}/clientes', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $datos = request()->validate([
            'nombre'         => 'required|string|max:255',
            'telefono'       => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:255',
            'rfc_nit'        => 'nullable|string|max:50',
            'direccion'      => 'nullable|string|max:500',
            'limite_credito' => 'nullable|numeric|min:0',
        ]);
        DB::table('clientes')->insert(array_merge($datos, [
            'sucursal_id' => $sucursal->id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]));
        return redirect("/{$slug}/clientes")->with('success', 'Cliente registrado correctamente');
    });

    Route::put('/{slug}/clientes/{id}', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $datos = request()->validate([
            'nombre'         => 'required|string|max:255',
            'telefono'       => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:255',
            'rfc_nit'        => 'nullable|string|max:50',
            'direccion'      => 'nullable|string|max:500',
            'limite_credito' => 'nullable|numeric|min:0',
        ]);
        DB::table('clientes')
            ->where('sucursal_id', $sucursal->id)
            ->where('id', $id)
            ->update(array_merge($datos, ['updated_at' => now()]));
        return redirect("/{$slug}/clientes")->with('success', 'Cliente actualizado correctamente');
    });

    Route::delete('/{slug}/clientes/{id}', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        DB::table('clientes')->where('sucursal_id', $sucursal->id)->where('id', $id)->delete();
        return redirect("/{$slug}/clientes")->with('success', 'Cliente eliminado');
    });

    // Reportes
    Route::get('/{slug}/reportes', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $sid = $sucursal->id;

        $ventasSemana = DB::table('ventas')
            ->selectRaw('DATE(fecha_venta) as dia, SUM(total) as total, COUNT(*) as cantidad')
            ->where('sucursal_id', $sid)
            ->where('fecha_venta', '>=', now()->subDays(6)->startOfDay())
            ->where('estado', '!=', 'cancelada')
            ->groupBy('dia')->orderBy('dia')->get();

        $ventasPago = DB::table('ventas')
            ->selectRaw('metodo_pago, SUM(total) as total, COUNT(*) as cantidad')
            ->where('sucursal_id', $sid)
            ->where('fecha_venta', '>=', now()->subDays(6)->startOfDay())
            ->where('estado', '!=', 'cancelada')
            ->groupBy('metodo_pago')->get();

        $productosTop = DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->selectRaw('productos.nombre, SUM(detalle_ventas.cantidad) as total_uds, SUM(detalle_ventas.subtotal) as total_bs')
            ->where('ventas.sucursal_id', $sid)
            ->where('ventas.fecha_venta', '>=', now()->subDays(29)->startOfDay())
            ->where('ventas.estado', '!=', 'cancelada')
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_uds')->limit(10)->get();

        $ventasMesQ  = DB::table('ventas')->where('sucursal_id', $sid)
            ->whereMonth('fecha_venta', now()->month)->whereYear('fecha_venta', now()->year)
            ->where('estado', '!=', 'cancelada');
        $totalMes    = $ventasMesQ->sum('total');
        $cantidadMes = $ventasMesQ->count();

        $totalHoy    = DB::table('ventas')->where('sucursal_id', $sid)
            ->whereDate('fecha_venta', today())->where('estado', '!=', 'cancelada')->sum('total');
        $cantidadHoy = DB::table('ventas')->where('sucursal_id', $sid)
            ->whereDate('fecha_venta', today())->where('estado', '!=', 'cancelada')->count();

        $gananciaCat = DB::table('detalle_ventas')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->selectRaw('COALESCE(categorias.nombre,"Sin Departamento") as categoria,
                         SUM(detalle_ventas.subtotal - (detalle_ventas.cantidad * productos.precio_compra)) as ganancia')
            ->where('ventas.sucursal_id', $sid)
            ->where('ventas.fecha_venta', '>=', now()->subDays(6)->startOfDay())
            ->where('ventas.estado', '!=', 'cancelada')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('ganancia')->get();

        return view('sucursal.reportes', compact(
            'sucursal', 'ventasSemana', 'ventasPago', 'productosTop',
            'totalMes', 'cantidadMes', 'totalHoy', 'cantidadHoy', 'gananciaCat'
        ));
    })->name('sucursal.reportes');

    Route::get('/{slug}/compras', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        return view('sucursal.compras', compact('sucursal'));
    })->name('sucursal.compras');

    Route::get('/{slug}/creditos', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        return view('sucursal.creditos', compact('sucursal'));
    })->name('sucursal.creditos');

    Route::get('/{slug}/facturas', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $ventas = DB::table('ventas')
            ->where('sucursal_id', $sucursal->id)
            ->orderBy('created_at', 'desc')
            ->get();
        return view('sucursal.facturas', compact('sucursal', 'ventas'));
    })->name('sucursal.facturas');

    Route::get('/{slug}/corte', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $ventasHoy = DB::table('ventas')
            ->where('sucursal_id', $sucursal->id)
            ->whereDate('fecha_venta', today())
            ->sum('total');
        $ventasPorMetodo = DB::table('ventas')
            ->where('sucursal_id', $sucursal->id)
            ->whereDate('fecha_venta', today())
            ->select('metodo_pago', DB::raw('SUM(total) as total'))
            ->groupBy('metodo_pago')
            ->get();
        return view('sucursal.corte', compact('sucursal', 'ventasHoy', 'ventasPorMetodo'));
    })->name('sucursal.corte');

    Route::get('/{slug}/configuracion', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        return view('sucursal.configuracion', compact('sucursal'));
    })->name('sucursal.configuracion');

    // Exportar Ventas a Excel
    Route::get('/{slug}/ventas/excel', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

        $ventas = DB::table('ventas')
            ->where('sucursal_id', $sucursal->id)
            ->orderBy('fecha_venta', 'desc')
            ->get();

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

        $pythonCandidates = PHP_OS_FAMILY === 'Windows'
            ? ['python', 'py', 'C:/Python312/python.exe', 'C:/Python311/python.exe', 'C:/Python310/python.exe']
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
            return back()->with('error', 'Error al generar Excel: ' . $process->getErrorOutput());
        }

        return response()->download($tmpFile, 'ventas_' . $slug . '_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    });

    // Exportar Inventario a Excel
    Route::get('/{slug}/inventario/excel', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

        $productos  = DB::table('productos')
            ->where('sucursal_id', $sucursal->id)
            ->orderBy('nombre')
            ->get();
        $categorias = DB::table('categorias')
            ->where('sucursal_id', $sucursal->id)
            ->get()
            ->keyBy('id');

        $tmpFile    = str_replace('\\', '/', sys_get_temp_dir()) . '/inventario_' . $slug . '_' . time() . '.xlsx';
        $scriptPath = str_replace('\\', '/', base_path('scripts/generar_excel.py'));

        $items = $productos->map(fn($p) => [
            'codigo_barras' => $p->codigo_barras ?? '',
            'nombre'        => $p->nombre,
            'categoria'     => $categorias->has($p->categoria_id) ? $categorias[$p->categoria_id]->nombre : 'Sin categoría',
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
            ? ['python', 'py', 'C:/Python312/python.exe', 'C:/Python311/python.exe', 'C:/Python310/python.exe']
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
            return back()->with('error', 'Error al generar Excel: ' . $process->getErrorOutput());
        }

        return response()->download($tmpFile, 'inventario_' . $slug . '_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    });

    // Detalle de venta (JSON para modal)
    Route::get('/{slug}/ventas/{id}/detalle', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

        $venta = DB::table('ventas')
            ->where('sucursal_id', $sucursal->id)
            ->where('id', $id)
            ->first();
        if (!$venta) return response()->json(['error' => 'No encontrada'], 404);

        $items = DB::table('detalle_ventas')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->select('productos.nombre', 'productos.codigo_barras',
                     'detalle_ventas.cantidad', 'detalle_ventas.precio_unitario', 'detalle_ventas.subtotal')
            ->where('detalle_ventas.venta_id', $id)
            ->get();

        return response()->json(['venta' => $venta, 'items' => $items]);
    });

    // Cancelar venta
    Route::post('/{slug}/ventas/{id}/cancelar', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

        $venta = DB::table('ventas')
            ->where('sucursal_id', $sucursal->id)
            ->where('id', $id)
            ->first();
        if (!$venta) return response()->json(['error' => 'No encontrada'], 404);
        if ($venta->estado === 'cancelada') return response()->json(['error' => 'Ya cancelada'], 400);

        try {
            DB::transaction(function () use ($id) {
                $items = DB::table('detalle_ventas')->where('venta_id', $id)->get();
                foreach ($items as $item) {
                    DB::table('productos')->where('id', $item->producto_id)
                        ->increment('stock_actual', $item->cantidad);
                }
                DB::table('ventas')->where('id', $id)->update([
                    'estado'     => 'cancelada',
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => 'No se pudo cancelar: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true]);
    });

    Route::get('/{slug}/ayuda', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        return view('sucursal.ayuda', compact('sucursal'));
    })->name('sucursal.ayuda');

    Route::get('/{slug}', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $sid = $sucursal->id;

        $ventasHoy          = DB::table('ventas')->where('sucursal_id', $sid)->whereDate('fecha_venta', today())->sum('total');
        $totalProductos     = DB::table('productos')->where('sucursal_id', $sid)->count();
        $productosBajoStock = DB::table('productos')->where('sucursal_id', $sid)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        return view('sucursal.dashboard', compact('sucursal', 'ventasHoy', 'totalProductos', 'productosBajoStock'));
    })->name('sucursal.dashboard');

    Route::get('/{slug}/productos', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $productos  = DB::table('productos')->where('sucursal_id', $sucursal->id)->get();
        $categorias = DB::table('categorias')->where('sucursal_id', $sucursal->id)->get();
        return view('sucursal.productos', compact('sucursal', 'productos', 'categorias'));
    })->name('sucursal.productos');

    Route::patch('/{slug}/productos/{id}/toggle', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $prod = DB::table('productos')->where('sucursal_id', $sucursal->id)->where('id', $id)->first();
        if (!$prod) return response()->json(['error' => 'No encontrado'], 404);

        $nuevoEstado = !$prod->activo;
        DB::table('productos')->where('id', $id)->update(['activo' => $nuevoEstado, 'updated_at' => now()]);

        return response()->json([
            'activo' => $nuevoEstado,
            'label'  => $nuevoEstado ? 'Activo' : 'Inactivo',
        ]);
    });

    Route::delete('/{slug}/productos/{id}', function ($slug, $id) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $prod = DB::table('productos')->where('sucursal_id', $sucursal->id)->where('id', $id)->first();
        if (!$prod) return redirect("/{$slug}/productos")->with('error', 'Producto no encontrado');

        $tieneVentas = DB::table('detalle_ventas')->where('producto_id', $id)->exists();
        if ($tieneVentas) {
            return redirect("/{$slug}/productos")->with('error',
                "No se puede eliminar \"{$prod->nombre}\" porque tiene ventas registradas.");
        }

        if ($prod->imagen) Storage::disk('public')->delete($prod->imagen);
        DB::table('productos')->where('id', $id)->delete();

        return redirect("/{$slug}/productos")->with('success', "Producto \"{$prod->nombre}\" eliminado correctamente");
    });

    Route::post('/{slug}/productos', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();

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
        ]);

        $datos['imagen']      = request()->file('imagen')->store("productos/{$slug}", 'public');
        $datos['sucursal_id'] = $sucursal->id;
        $datos['activo']      = true;
        $datos['created_at']  = now();
        $datos['updated_at']  = now();

        DB::table('productos')->insert($datos);
        return redirect("/{$slug}/productos")->with('success', 'Producto creado correctamente');
    });

    Route::get('/{slug}/ventas', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $ventas = DB::table('ventas')
            ->where('sucursal_id', $sucursal->id)
            ->orderBy('created_at', 'desc')
            ->get();
        return view('sucursal.ventas', compact('sucursal', 'ventas'));
    })->name('sucursal.ventas');

    // Punto de Venta
    Route::get('/{slug}/pos', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $productos = DB::table('productos')
            ->where('sucursal_id', $sucursal->id)
            ->where('stock_actual', '>', 0)
            ->where('activo', true)
            ->whereNull('deleted_at')
            ->get();
        return view('sucursal.pos', compact('sucursal', 'productos'));
    })->name('sucursal.pos');

    Route::post('/{slug}/pos/venta', function ($slug) {
        $sucursal = Sucursal::where('slug', $slug)->firstOrFail();
        $sid = $sucursal->id;

        $datos = request()->validate([
            'items'                  => 'required|array',
            'items.*.producto_id'    => 'required|integer',
            'items.*.cantidad'       => 'required|integer|min:1',
            'items.*.precio'         => 'required|numeric',
            'total'                  => 'required|numeric',
            'metodo_pago'            => 'required|in:efectivo,tarjeta,transferencia',
        ]);

        $folio    = 'VENTA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $ivaRate  = (float) config('negocio.iva', 0.13);
        $subtotal = $datos['total'] / (1 + $ivaRate);
        $iva      = $datos['total'] - $subtotal;

        try {
            $ventaId = DB::transaction(function () use ($datos, $folio, $subtotal, $iva, $sid) {
                $ids    = collect($datos['items'])->pluck('producto_id')->all();
                $stocks = DB::table('productos')
                    ->where('sucursal_id', $sid)
                    ->whereIn('id', $ids)
                    ->lockForUpdate()
                    ->pluck('stock_actual', 'id');

                foreach ($datos['items'] as $item) {
                    if (($stocks[$item['producto_id']] ?? 0) < $item['cantidad']) {
                        throw new \RuntimeException('Stock insuficiente para producto ID ' . $item['producto_id']);
                    }
                }

                $ventaId = DB::table('ventas')->insertGetId([
                    'sucursal_id' => $sid,
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
                    DB::table('detalle_ventas')->insert([
                        'venta_id'        => $ventaId,
                        'producto_id'     => $item['producto_id'],
                        'cantidad'        => $item['cantidad'],
                        'precio_unitario' => $item['precio'],
                        'subtotal'        => $item['cantidad'] * $item['precio'],
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    DB::table('productos')->where('id', $item['producto_id'])
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
