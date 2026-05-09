<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $sucursal->nombre ?? 'Elixirdorado' }} - @yield('titulo', 'Panel')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {{-- Chart.js solo se carga en vistas que lo declaren con @section('needs-charts') para no penalizar PCs antiguas en el POS --}}
    @hasSection('needs-charts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    @endif
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .nav-btn {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 12px; font-size: 13px; font-weight: 500;
            border: 1px solid #c8d6e5; border-radius: 4px;
            background: linear-gradient(180deg, #f8fafc 0%, #e8edf2 100%);
            color: #2d3748; cursor: pointer; transition: all 0.15s; white-space: nowrap;
        }
        .nav-btn:hover { background: linear-gradient(180deg, #e3eaf3 0%, #ccd8e6 100%); border-color: #a0b3c6; }
        .nav-btn.active { background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%); color: white; border-color: #1d4ed8; }
        .nav-btn .shortcut { font-size: 10px; opacity: 0.7; font-weight: 600; }
        .topbar { background: linear-gradient(180deg, #1e3a5f 0%, #152b47 100%); }
        .module-bar { background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%); border-bottom: 1px solid #cbd5e1; }
        .section-bar { background: linear-gradient(180deg, #e8edf5 0%, #dce4ef 100%); border-bottom: 1px solid #c1cfe0; padding: 6px 12px; }
        .content-area { background: #f8fafc; min-height: calc(100vh - 110px); }
        .status-bar { background: linear-gradient(180deg, #e2e8f0 0%, #d1dce8 100%); border-top: 1px solid #c1cfe0; font-size: 11px; color: #64748b; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .btn-primary { background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%); color: white; border: 1px solid #1d4ed8; padding: 8px 16px; border-radius: 4px; font-weight: 500; cursor: pointer; }
        .btn-primary:hover { background: linear-gradient(180deg, #2563eb 0%, #1e40af 100%); }
        .btn-success { background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%); color: white; border: 1px solid #15803d; padding: 8px 16px; border-radius: 4px; font-weight: 500; cursor: pointer; }
        .btn-success:hover { background: linear-gradient(180deg, #16a34a 0%, #14532d 100% ); }
        .btn-danger { background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%); color: white; border: 1px solid #b91c1c; padding: 8px 16px; border-radius: 4px; font-weight: 500; cursor: pointer; }
        .btn-secondary { background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%); color: #374151; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 4px; font-weight: 500; cursor: pointer; }
        .table-header { background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%); }
        .table-row:hover { background: #f0f7ff; }
        .badge-green { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-red { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-yellow { background: #fef9c3; color: #92400e; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-blue { background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .input-field { border: 1px solid #d1d5db; border-radius: 4px; padding: 8px 12px; width: 100%; font-size: 14px; }
        .input-field:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.2); }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; display: flex; align-items: center; justify-content: center; }
        .modal-box { background: white; border-radius: 8px; padding: 24px; min-width: 400px; max-width: 600px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        @yield('extra-styles')
        /* Ocultar UI en impresión por defecto (las vistas sobreescriben según necesiten) */
        @media print {
            .topbar, .module-bar, .section-bar, .status-bar { display: none !important; }
        }
    </style>
    @yield('head')
</head>
<body class="bg-gray-100">

    <!-- TOP BAR - Logo y info de usuario -->
    <div class="topbar px-4 py-2 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="text-yellow-400 text-2xl">🥃</div>
            <div>
                <div class="text-white font-bold text-lg leading-tight">elixirdorado</div>
                <div class="text-blue-300 text-xs">PUNTO DE VENTA</div>
            </div>
            <div class="ml-4 text-white text-sm font-semibold border-l border-blue-400 pl-4">
                {{ $sucursal->nombre ?? '' }}
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <div class="text-blue-200 text-xs">Le atiende:</div>
                <div class="text-white text-sm font-semibold">{{ auth()->user()->name ?? 'Usuario' }}</div>
            </div>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="text-blue-300 hover:text-white text-sm flex items-center gap-1">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </button>
            </form>
        </div>
    </div>

    <!-- MODULE NAVIGATION BAR -->
    <div class="module-bar px-3 py-2 flex items-center gap-2 flex-wrap">
        <a href="/{{ $sucursal->slug }}/pos" class="nav-btn {{ request()->is('*/pos') ? 'active' : '' }}">
            <i class="fas fa-cash-register text-blue-600 {{ request()->is('*/pos') ? 'text-white' : '' }}"></i>
            <span class="shortcut">F1</span> Ventas
        </a>
        <a href="/{{ $sucursal->slug }}/creditos" class="nav-btn {{ request()->is('*/creditos') ? 'active' : '' }}">
            <i class="fas fa-credit-card text-purple-600 {{ request()->is('*/creditos') ? 'text-white' : '' }}"></i>
            <span class="shortcut">F2</span> Créditos
        </a>
        <a href="/{{ $sucursal->slug }}/clientes" class="nav-btn {{ request()->is('*/clientes') ? 'active' : '' }}">
            <i class="fas fa-users text-green-600 {{ request()->is('*/clientes') ? 'text-white' : '' }}"></i>
            Clientes
        </a>
        <a href="/{{ $sucursal->slug }}/productos" class="nav-btn {{ request()->is('*/productos') ? 'active' : '' }}">
            <i class="fas fa-box text-orange-500 {{ request()->is('*/productos') ? 'text-white' : '' }}"></i>
            <span class="shortcut">F3</span> Productos
        </a>
        <a href="/{{ $sucursal->slug }}/inventario" class="nav-btn {{ request()->is('*/inventario*') ? 'active' : '' }}">
            <i class="fas fa-warehouse text-teal-600 {{ request()->is('*/inventario*') ? 'text-white' : '' }}"></i>
            <span class="shortcut">F4</span> Inventario
        </a>
        <a href="/{{ $sucursal->slug }}/compras" class="nav-btn {{ request()->is('*/compras') ? 'active' : '' }}">
            <i class="fas fa-shopping-cart text-indigo-600 {{ request()->is('*/compras') ? 'text-white' : '' }}"></i>
            Compras
        </a>
        <a href="/{{ $sucursal->slug }}/configuracion" class="nav-btn {{ request()->is('*/configuracion') ? 'active' : '' }}">
            <i class="fas fa-cog text-gray-600 {{ request()->is('*/configuracion') ? 'text-white' : '' }}"></i>
            Configuración
        </a>
        <a href="/{{ $sucursal->slug }}/facturas" class="nav-btn {{ request()->is('*/facturas') ? 'active' : '' }}">
            <i class="fas fa-file-invoice text-red-500 {{ request()->is('*/facturas') ? 'text-white' : '' }}"></i>
            Facturas
        </a>
        <a href="/{{ $sucursal->slug }}/corte" class="nav-btn {{ request()->is('*/corte') ? 'active' : '' }}">
            <i class="fas fa-cut text-pink-600 {{ request()->is('*/corte') ? 'text-white' : '' }}"></i>
            Corte
        </a>
        <a href="/{{ $sucursal->slug }}/reportes" class="nav-btn {{ request()->is('*/reportes') ? 'active' : '' }}">
            <i class="fas fa-chart-bar text-blue-600 {{ request()->is('*/reportes') ? 'text-white' : '' }}"></i>
            Reportes
        </a>
        @if(auth()->user() && in_array(auth()->user()->rol, ['super_admin', 'admin']))
        <a href="/admin" class="nav-btn ml-auto">
            <i class="fas fa-building text-gray-700"></i>
            Panel Admin
        </a>
        @endif
        <a href="/{{ $sucursal->slug }}/ayuda" class="nav-btn {{ request()->is('*/ayuda') ? 'active' : '' }}" title="Manual de usuario (Ayuda)">
            <i class="fas fa-question-circle text-blue-500 {{ request()->is('*/ayuda') ? 'text-white' : '' }}"></i>
            Ayuda
        </a>
    </div>

    <!-- CONTENT -->
    <div class="content-area">
        @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-800 px-4 py-3 flex items-center justify-between" id="alert-success">
            <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
            <button onclick="document.getElementById('alert-success').remove()" class="text-green-600 hover:text-green-800"><i class="fas fa-times"></i></button>
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-800 px-4 py-3 flex items-center justify-between" id="alert-error">
            <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
            <button onclick="document.getElementById('alert-error').remove()" class="text-red-600 hover:text-red-800"><i class="fas fa-times"></i></button>
        </div>
        @endif
        @yield('content')
    </div>

    <!-- STATUS BAR -->
    <div class="status-bar px-4 py-1 flex items-center justify-between fixed bottom-0 left-0 right-0">
        <span>elixirdorado (v1.0). Todos los derechos reservados.</span>
        <span>{{ $sucursal->nombre ?? '' }} | {{ now()->format('d - M    g:i a') }}</span>
    </div>

    <!-- ══ MODAL DE ADVERTENCIA DE SESIÓN ═══════════════════════════════════ -->
    <div id="session-warning" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:12px; padding:32px; max-width:400px; width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.4);">
            <div style="font-size:48px; margin-bottom:12px;">⏰</div>
            <h2 style="font-size:18px; font-weight:800; color:#1e3a5f; margin-bottom:8px;">¿Sigues ahí?</h2>
            <p style="color:#64748b; font-size:14px; margin-bottom:20px;">
                Tu sesión cerrará en <strong id="session-countdown" style="color:#dc2626; font-size:18px;">60</strong> segundos por inactividad.
            </p>
            <div style="display:flex; gap:12px; justify-content:center;">
                <button onclick="extenderSesion()" style="background:#2563eb; color:white; border:none; padding:10px 24px; border-radius:6px; font-weight:700; cursor:pointer; font-size:14px;">
                    <i class="fas fa-check mr-1"></i> Sí, continuar
                </button>
                <form method="POST" action="/logout" style="margin:0;">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <button type="submit" style="background:#f1f5f9; color:#374151; border:1px solid #d1d5db; padding:10px 24px; border-radius:6px; font-weight:700; cursor:pointer; font-size:14px;">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ══ TOAST DE NOTIFICACIONES ════════════════════════════════════════ -->
    <div id="toast-container" style="position:fixed; bottom:48px; right:16px; z-index:9998; display:flex; flex-direction:column; gap:8px;"></div>

    <script>
    // ── Keyboard shortcuts ────────────────────────────────────────────────────
    document.addEventListener('keydown', function(e) {
        @yield('keyboard-shortcuts')
        if (e.key === 'F1') { e.preventDefault(); window.location.href = '/{{ $sucursal->slug }}/pos'; }
        if (e.key === 'F2') { e.preventDefault(); window.location.href = '/{{ $sucursal->slug }}/creditos'; }
        if (e.key === 'F3') { e.preventDefault(); window.location.href = '/{{ $sucursal->slug }}/productos'; }
        if (e.key === 'F4') { e.preventDefault(); window.location.href = '/{{ $sucursal->slug }}/inventario'; }
    });

    // ── Prevenir doble-submit en TODOS los formularios ────────────────────────
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const btns = this.querySelectorAll('button[type="submit"], input[type="submit"]');
            btns.forEach(btn => {
                btn.disabled = true;
                if (btn.tagName === 'BUTTON') {
                    btn.dataset.original = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Procesando...';
                }
            });
            // Rehabilitar después de 10s por si hay error de red
            setTimeout(() => {
                btns.forEach(btn => {
                    btn.disabled = false;
                    if (btn.dataset.original) btn.innerHTML = btn.dataset.original;
                });
            }, 10000);
        });
    });

    // ── Toast global para mensajes ────────────────────────────────────────────
    window.showToast = function(msg, type = 'success', duration = 4000) {
        const colors = {
            success: { bg: '#dcfce7', border: '#16a34a', text: '#166534', icon: 'fa-check-circle' },
            error:   { bg: '#fee2e2', border: '#dc2626', text: '#991b1b', icon: 'fa-exclamation-circle' },
            warning: { bg: '#fef9c3', border: '#ca8a04', text: '#92400e', icon: 'fa-exclamation-triangle' },
            info:    { bg: '#dbeafe', border: '#2563eb', text: '#1e40af', icon: 'fa-info-circle' },
        };
        const c = colors[type] || colors.info;
        const toast = document.createElement('div');
        toast.style.cssText = `background:${c.bg}; border-left:4px solid ${c.border}; color:${c.text};
            padding:12px 16px; border-radius:6px; font-size:13px; font-weight:600;
            box-shadow:0 4px 12px rgba(0,0,0,0.15); display:flex; align-items:center; gap:10px;
            max-width:320px; animation:slideInRight 0.3s ease;`;
        toast.innerHTML = `<i class="fas ${c.icon}"></i><span style="flex:1">${msg}</span>
            <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:${c.text};opacity:0.6;">✕</button>`;
        document.getElementById('toast-container').appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, duration);
    };

    // Mostrar toasts de sesión flash como toasts si están en la URL
    @if(session('success'))
        setTimeout(() => showToast('{{ addslashes(session("success")) }}', 'success'), 300);
    @endif
    @if(session('error'))
        setTimeout(() => showToast('{{ addslashes(session("error")) }}', 'error'), 300);
    @endif

    // ── Timeout de sesión por inactividad (20 minutos) ────────────────────────
    let idleTimer, countdownTimer, idleSeconds = 0;
    const IDLE_LIMIT  = 20 * 60; // 20 minutos
    const WARN_BEFORE = 60;      // avisar 60s antes

    function resetIdle() {
        idleSeconds = 0;
        const warn = document.getElementById('session-warning');
        if (warn.style.display === 'flex') return; // no reiniciar si ya está visible
        clearTimeout(idleTimer);
        idleTimer = setTimeout(mostrarAvisoSesion, (IDLE_LIMIT - WARN_BEFORE) * 1000);
    }

    function mostrarAvisoSesion() {
        document.getElementById('session-warning').style.display = 'flex';
        let secs = WARN_BEFORE;
        document.getElementById('session-countdown').textContent = secs;
        countdownTimer = setInterval(() => {
            secs--;
            document.getElementById('session-countdown').textContent = secs;
            if (secs <= 0) {
                clearInterval(countdownTimer);
                document.querySelector('#session-warning form').submit();
            }
        }, 1000);
    }

    window.extenderSesion = function() {
        clearInterval(countdownTimer);
        document.getElementById('session-warning').style.display = 'none';
        resetIdle();
        showToast('Sesión extendida correctamente.', 'info', 2500);
    };

    ['mousemove','keydown','click','scroll','touchstart'].forEach(e =>
        document.addEventListener(e, resetIdle, { passive: true }));
    resetIdle();
    </script>
    <style>
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to   { transform: translateX(0);   opacity: 1; }
    }
    </style>
    @yield('scripts')
</body>
</html>
