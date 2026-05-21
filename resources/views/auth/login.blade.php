<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elixir Dorado — Acceso</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex">

    <!-- Left panel (branding) -->
    <div class="hidden lg:flex w-1/2 bg-slate-800 flex-col justify-between p-12">
        <div>
            <div class="flex items-center gap-3 mb-16">
                <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center text-white font-bold text-lg">E</div>
                <span class="text-white font-bold text-xl tracking-tight">Elixir Dorado</span>
            </div>
            <h1 class="text-4xl font-bold text-white leading-tight mb-4">
                Sistema de gestión<br>multi-sucursal
            </h1>
            <p class="text-slate-400 text-lg">
                Ventas, inventario, reportes y pedidos en un solo lugar.
            </p>
        </div>

        <!-- Feature pills -->
        <div class="space-y-3">
            @foreach(['Point of Sale en tiempo real', 'Control de inventario por sucursal', 'Reportes y analítica global'] as $feat)
            <div class="flex items-center gap-3 text-slate-300 text-sm">
                <div class="w-5 h-5 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                {{ $feat }}
            </div>
            @endforeach
        </div>
    </div>

    <!-- Right panel (form) -->
    <div class="flex-1 flex items-center justify-center p-6 bg-gray-50">
        <div class="w-full max-w-sm">

            <!-- Mobile logo -->
            <div class="flex items-center gap-3 mb-10 lg:hidden">
                <div class="w-9 h-9 bg-amber-500 rounded-xl flex items-center justify-center text-white font-bold">E</div>
                <span class="font-bold text-gray-800 text-lg">Elixir Dorado</span>
            </div>

            <h2 class="text-2xl font-bold text-gray-800 mb-1">Bienvenido</h2>
            <p class="text-gray-500 text-sm mb-8">Ingresa tus credenciales para continuar</p>

            @if($errors->any())
            <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-4 py-3 mb-6 text-sm">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Usuario o correo electrónico</label>
                    <input type="text" name="login" value="{{ old('login') }}" required autofocus
                        class="w-full border border-gray-200 bg-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-shadow placeholder-gray-400"
                        placeholder="nickname o email">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                            class="w-full border border-gray-200 bg-white rounded-xl px-4 py-3 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-shadow placeholder-gray-400"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors p-1"
                            tabindex="-1" aria-label="Mostrar contraseña">
                            <svg id="ojo-cerrado" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            <svg id="ojo-abierto" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-semibold py-3 rounded-xl text-sm transition-colors mt-2">
                    Iniciar sesión
                </button>
            </form>

            <p class="text-center text-gray-400 text-xs mt-8">
                Elixir Dorado &copy; {{ date('Y') }} — Sistema interno
            </p>
        </div>
    </div>

<script>
function togglePassword() {
    const input      = document.getElementById('password');
    const ojoCerrado = document.getElementById('ojo-cerrado');
    const ojoAbierto = document.getElementById('ojo-abierto');
    const visible    = input.type === 'text';
    input.type       = visible ? 'password' : 'text';
    ojoCerrado.classList.toggle('hidden',  visible);
    ojoAbierto.classList.toggle('hidden', !visible);
}
</script>
</body>
</html>
