<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bienvenido — Configuración Inicial · Elixirdorado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #1e3a5f 0%, #152b47 50%, #0f1f33 100%); min-height: 100vh; }
        .glass { background: rgba(255,255,255,0.97); border-radius: 16px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); }
        .step { display: none; }
        .step.active { display: block; }
        .step-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; transition: all 0.3s; }
        .step-dot.done { background: #16a34a; color: white; }
        .step-dot.active { background: #2563eb; color: white; box-shadow: 0 0 0 4px rgba(37,99,235,0.3); }
        .step-dot.pending { background: #e2e8f0; color: #94a3b8; }
        .input-field { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; width: 100%; font-size: 14px; transition: all 0.2s; }
        .input-field:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
        .input-field.error { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.15); }
        .btn-primary { background: linear-gradient(180deg, #3b82f6, #2563eb); color: white; border: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 15px; transition: all 0.2s; }
        .btn-primary:hover { background: linear-gradient(180deg, #2563eb, #1d4ed8); transform: translateY(-1px); }
        .btn-secondary { background: #f1f5f9; color: #374151; border: 1px solid #d1d5db; padding: 12px 28px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 15px; }
        .floating-label { position: relative; }
        .floating-label label { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 14px; pointer-events: none; transition: all 0.2s; background: white; padding: 0 4px; }
        .floating-label input:focus ~ label,
        .floating-label input:not(:placeholder-shown) ~ label { top: 0; font-size: 11px; color: #2563eb; }
        .animate-bounce-slow { animation: bounce 2s infinite; }
        @keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
        .progress-bar { height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #2563eb, #3b82f6); border-radius: 2px; transition: width 0.4s ease; }
    </style>
</head>
<body class="flex items-center justify-center p-4 min-h-screen">

    <!-- Stars background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        @for($i = 0; $i < 30; $i++)
        <div style="position:absolute; width:2px; height:2px; background:rgba(255,255,255,{{ rand(2,8)/10 }}); border-radius:50%;
                    top:{{ rand(0,100) }}%; left:{{ rand(0,100) }}%;"></div>
        @endfor
    </div>

    <div class="glass w-full max-w-2xl p-0 overflow-hidden">

        <!-- Header con logo -->
        <div style="background: linear-gradient(135deg, #1e3a5f, #2563eb); padding: 32px 40px;">
            <div class="flex items-center gap-4">
                <div class="text-5xl animate-bounce-slow">🥃</div>
                <div>
                    <h1 class="text-white text-3xl font-black tracking-wide">elixirdorado</h1>
                    <p class="text-blue-200 text-sm mt-1">Sistema de Punto de Venta · Multi-Sucursal</p>
                </div>
            </div>
        </div>

        <!-- Progress steps -->
        <div class="px-10 py-5 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center gap-3">
                <div class="step-dot active" id="dot-1">1</div>
                <div class="flex-1 progress-bar"><div class="progress-fill" id="prog-1-2" style="width:0%"></div></div>
                <div class="step-dot pending" id="dot-2">2</div>
                <div class="flex-1 progress-bar"><div class="progress-fill" id="prog-2-3" style="width:0%"></div></div>
                <div class="step-dot pending" id="dot-3">3</div>
                <div class="flex-1 progress-bar"><div class="progress-fill" id="prog-3-4" style="width:0%"></div></div>
                <div class="step-dot pending" id="dot-4"><i class="fas fa-check text-xs"></i></div>
            </div>
            <div class="flex justify-between text-xs text-gray-400 mt-2">
                <span>Bienvenida</span>
                <span>Sucursal</span>
                <span>Administrador</span>
                <span>Listo</span>
            </div>
        </div>

        <!-- Formulario -->
        <form method="POST" action="/admin/setup" id="setup-form">
            @csrf

            <!-- PASO 1: Bienvenida -->
            <div class="step active px-10 py-8" id="step-1">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-rocket text-blue-600 text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-black text-gray-800 mb-2">¡Bienvenido a Elixirdorado!</h2>
                    <p class="text-gray-500 text-sm leading-relaxed max-w-md mx-auto">
                        Este asistente te guiará para configurar tu primer punto de venta en menos de 2 minutos.
                        Solo necesitas el nombre de tu sucursal y los datos del administrador.
                    </p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h3 class="font-bold text-blue-800 mb-2 text-sm"><i class="fas fa-info-circle mr-2"></i>¿Qué podrás hacer con el sistema?</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm text-blue-700">
                        <div><i class="fas fa-check mr-1 text-green-500"></i>Registrar ventas con código de barras</div>
                        <div><i class="fas fa-check mr-1 text-green-500"></i>Controlar inventario y stock</div>
                        <div><i class="fas fa-check mr-1 text-green-500"></i>Gestionar múltiples sucursales</div>
                        <div><i class="fas fa-check mr-1 text-green-500"></i>Reportes y análisis de ventas</div>
                        <div><i class="fas fa-check mr-1 text-green-500"></i>Exportar reportes a Excel</div>
                        <div><i class="fas fa-check mr-1 text-green-500"></i>Imprimir tickets y facturas</div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="button" onclick="nextStep(2)" class="btn-primary">
                        Comenzar configuración <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>

            <!-- PASO 2: Datos de la sucursal -->
            <div class="step px-10 py-8" id="step-2">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-gray-800 mb-1">Datos de la Sucursal</h2>
                    <p class="text-gray-400 text-sm">Esta será tu primera sucursal. Puedes agregar más desde el panel de administración.</p>
                </div>

                @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <p class="text-red-700 text-sm font-semibold"><i class="fas fa-exclamation-triangle mr-2"></i>Por favor corrige los siguientes errores:</p>
                    <ul class="mt-2 list-disc list-inside text-sm text-red-600">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 block mb-1">Nombre de la sucursal *</label>
                        <input type="text" name="nombre" class="input-field {{ $errors->has('nombre') ? 'error' : '' }}"
                            placeholder="Ej: Sucursal Centro, Tienda Principal..."
                            value="{{ old('nombre') }}" required>
                        <p class="text-xs text-gray-400 mt-1">Este nombre aparecerá en los tickets y reportes.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-gray-700 block mb-1">Slug (identificador) *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">/</span>
                                <input type="text" name="slug" id="slug" class="input-field pl-6 {{ $errors->has('slug') ? 'error' : '' }}"
                                    placeholder="centro" value="{{ old('slug') }}" required
                                    oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9-]/g,'')">
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Solo letras, números y guiones. Ej: centro, norte, tienda-1</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700 block mb-1">Teléfono</label>
                            <input type="text" name="telefono" class="input-field"
                                placeholder="75000000" value="{{ old('telefono') }}">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-700 block mb-1">Dirección</label>
                        <input type="text" name="direccion" class="input-field"
                            placeholder="Av. Principal #123, Ciudad" value="{{ old('direccion') }}">
                    </div>
                </div>

                <!-- Auto-generar slug desde nombre -->
                <script>
                    document.querySelector('[name="nombre"]').addEventListener('input', function() {
                        const slug = document.getElementById('slug');
                        if (!slug.dataset.modified) {
                            slug.value = this.value.toLowerCase()
                                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                                .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                        }
                    });
                    document.getElementById('slug').addEventListener('input', function() {
                        this.dataset.modified = 'true';
                    });
                </script>

                <div class="flex justify-between mt-8">
                    <button type="button" onclick="nextStep(1)" class="btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>Atrás
                    </button>
                    <button type="button" onclick="validateStep2()" class="btn-primary">
                        Continuar <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>

            <!-- PASO 3: Datos del administrador -->
            <div class="step px-10 py-8" id="step-3">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-gray-800 mb-1">Cuenta de Administrador</h2>
                    <p class="text-gray-400 text-sm">El administrador podrá acceder a todas las sucursales y configuraciones.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 block mb-1">Nombre completo *</label>
                        <input type="text" name="admin_nombre" class="input-field"
                            placeholder="Nombre del administrador" value="{{ old('admin_nombre') }}" required>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-700 block mb-1">Correo electrónico *</label>
                        <input type="email" name="admin_email" class="input-field"
                            placeholder="admin@mitienda.com" value="{{ old('admin_email') }}" required>
                        <p class="text-xs text-gray-400 mt-1">Este será el usuario para iniciar sesión.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-gray-700 block mb-1">Contraseña *</label>
                            <div class="relative">
                                <input type="password" name="admin_password" id="pwd" class="input-field pr-10"
                                    placeholder="Mínimo 8 caracteres" required minlength="8">
                                <button type="button" onclick="togglePwd('pwd','eye-pwd')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye text-sm" id="eye-pwd"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700 block mb-1">Confirmar contraseña *</label>
                            <div class="relative">
                                <input type="password" name="admin_password_confirm" id="pwd2" class="input-field pr-10"
                                    placeholder="Repetir contraseña" required>
                                <button type="button" onclick="togglePwd('pwd2','eye-pwd2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye text-sm" id="eye-pwd2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Indicador de fuerza de contraseña -->
                    <div id="pwd-strength" class="hidden">
                        <div class="flex gap-1 mb-1">
                            <div class="h-1 flex-1 rounded" id="ps-1"></div>
                            <div class="h-1 flex-1 rounded" id="ps-2"></div>
                            <div class="h-1 flex-1 rounded" id="ps-3"></div>
                            <div class="h-1 flex-1 rounded" id="ps-4"></div>
                        </div>
                        <p class="text-xs" id="ps-label"></p>
                    </div>
                </div>

                <div class="flex justify-between mt-8">
                    <button type="button" onclick="nextStep(2)" class="btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>Atrás
                    </button>
                    <button type="button" onclick="validateStep3()" class="btn-primary">
                        Revisar y finalizar <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>

            <!-- PASO 4: Confirmación -->
            <div class="step px-10 py-8" id="step-4">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                    </div>
                    <h2 class="text-xl font-black text-gray-800 mb-2">¡Todo listo para comenzar!</h2>
                    <p class="text-gray-400 text-sm">Revisa los datos antes de guardar:</p>
                </div>

                <div class="bg-gray-50 rounded-lg p-5 border border-gray-200 space-y-3 text-sm mb-6">
                    <div class="flex justify-between"><span class="text-gray-500">Sucursal:</span><span class="font-bold text-gray-800" id="conf-nombre">-</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Identificador:</span><span class="font-mono text-blue-600" id="conf-slug">-</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Dirección:</span><span class="text-gray-700" id="conf-dir">-</span></div>
                    <div class="border-t border-gray-200 pt-3 flex justify-between"><span class="text-gray-500">Administrador:</span><span class="font-bold text-gray-800" id="conf-admin">-</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Email de acceso:</span><span class="text-blue-600" id="conf-email">-</span></div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <p class="text-yellow-800 text-sm">
                        <i class="fas fa-exclamation-triangle mr-2 text-yellow-500"></i>
                        <strong>Guarda tus credenciales.</strong> Si pierdes la contraseña necesitarás acceso a la base de datos para restablecerla.
                    </p>
                </div>

                <div class="flex justify-between">
                    <button type="button" onclick="nextStep(3)" class="btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>Corregir
                    </button>
                    <button type="submit" id="btn-submit" class="btn-primary" onclick="this.disabled=true; this.innerHTML='<i class=\'fas fa-spinner fa-spin mr-2\'></i>Configurando...'">
                        <i class="fas fa-rocket mr-2"></i>¡Iniciar sistema!
                    </button>
                </div>
            </div>

        </form>
    </div>

    <script>
    let currentStep = 1;

    function nextStep(step) {
        document.getElementById('step-' + currentStep).classList.remove('active');
        document.getElementById('step-' + step).classList.add('active');

        // Actualizar dots
        for (let i = 1; i <= 4; i++) {
            const dot = document.getElementById('dot-' + i);
            dot.classList.remove('done', 'active', 'pending');
            if (i < step) dot.classList.add('done'), dot.innerHTML = '<i class="fas fa-check text-xs"></i>';
            else if (i === step) dot.classList.add('active'), dot.textContent = i;
            else dot.classList.add('pending'), dot.textContent = i;
        }

        // Progress bars
        if (step > 1) document.getElementById('prog-1-2').style.width = '100%';
        if (step > 2) document.getElementById('prog-2-3').style.width = '100%';
        if (step > 3) document.getElementById('prog-3-4').style.width = '100%';

        currentStep = step;
        if (step === 4) fillConfirmation();
    }

    function validateStep2() {
        const nombre = document.querySelector('[name="nombre"]').value.trim();
        const slug   = document.querySelector('[name="slug"]').value.trim();
        if (!nombre) { alert('⚠️ Escribe el nombre de la sucursal.'); return; }
        if (!slug)   { alert('⚠️ Escribe el identificador (slug).'); return; }
        if (!/^[a-z0-9-]+$/.test(slug)) { alert('⚠️ El slug solo puede tener letras minúsculas, números y guiones.'); return; }
        nextStep(3);
    }

    function validateStep3() {
        const pwd  = document.getElementById('pwd').value;
        const pwd2 = document.getElementById('pwd2').value;
        const email = document.querySelector('[name="admin_email"]').value;
        const nombre = document.querySelector('[name="admin_nombre"]').value;
        if (!nombre.trim()) { alert('⚠️ Escribe el nombre del administrador.'); return; }
        if (!email.includes('@')) { alert('⚠️ El correo no es válido.'); return; }
        if (pwd.length < 8) { alert('⚠️ La contraseña debe tener al menos 8 caracteres.'); return; }
        if (pwd !== pwd2) { alert('⚠️ Las contraseñas no coinciden.'); return; }
        nextStep(4);
    }

    function fillConfirmation() {
        document.getElementById('conf-nombre').textContent = document.querySelector('[name="nombre"]').value || '-';
        document.getElementById('conf-slug').textContent   = '/' + (document.querySelector('[name="slug"]').value || '-');
        document.getElementById('conf-dir').textContent    = document.querySelector('[name="direccion"]').value || 'No especificada';
        document.getElementById('conf-admin').textContent  = document.querySelector('[name="admin_nombre"]').value || '-';
        document.getElementById('conf-email').textContent  = document.querySelector('[name="admin_email"]').value || '-';
    }

    function togglePwd(id, iconId) {
        const f = document.getElementById(id);
        const i = document.getElementById(iconId);
        if (f.type === 'password') { f.type = 'text'; i.classList.replace('fa-eye','fa-eye-slash'); }
        else { f.type = 'password'; i.classList.replace('fa-eye-slash','fa-eye'); }
    }

    // Indicador fuerza de contraseña
    document.getElementById('pwd').addEventListener('input', function() {
        const v = this.value;
        const div = document.getElementById('pwd-strength');
        if (!v) { div.classList.add('hidden'); return; }
        div.classList.remove('hidden');
        let score = 0;
        if (v.length >= 8) score++;
        if (/[A-Z]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        const colors = ['bg-red-400','bg-orange-400','bg-yellow-400','bg-green-500'];
        const labels = ['Muy débil','Débil','Aceptable','Fuerte'];
        const txtColors = ['text-red-500','text-orange-500','text-yellow-600','text-green-600'];
        for (let i = 1; i <= 4; i++) {
            const bar = document.getElementById('ps-' + i);
            bar.className = 'h-1 flex-1 rounded ' + (i <= score ? colors[score-1] : 'bg-gray-200');
        }
        document.getElementById('ps-label').className = 'text-xs ' + txtColors[score-1];
        document.getElementById('ps-label').textContent = labels[score-1];
    });

    @if($errors->any())
    // Si hay errores de servidor, ir al paso 2
    nextStep(2);
    @endif
    </script>
</body>
</html>
