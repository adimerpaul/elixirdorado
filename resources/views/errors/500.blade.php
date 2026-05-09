<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del servidor — Elixirdorado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #1e3a5f 0%, #152b47 100%); min-height: 100vh; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="text-center max-w-md">
        <div class="text-7xl mb-6">⚠️</div>
        <div class="text-9xl font-black text-white opacity-20 leading-none mb-2">500</div>
        <h1 class="text-2xl font-black text-white mb-3">Error interno del servidor</h1>
        <p class="text-blue-200 mb-6 text-sm leading-relaxed">
            Algo salió mal en el servidor.<br>
            No es un problema de tu computadora ni del navegador.<br>
            El técnico debe revisar los registros de error.
        </p>
        <div class="bg-white bg-opacity-10 rounded-lg p-4 mb-6 text-left">
            <p class="text-blue-200 text-xs font-semibold mb-2 uppercase tracking-wide">Qué puedes hacer:</p>
            <ul class="text-blue-100 text-sm space-y-1">
                <li><i class="fas fa-redo mr-2 text-blue-300"></i>Recarga la página con F5</li>
                <li><i class="fas fa-clock mr-2 text-blue-300"></i>Espera un momento e intenta de nuevo</li>
                <li><i class="fas fa-headset mr-2 text-blue-300"></i>Si persiste, contacta al administrador</li>
            </ul>
        </div>
        <div class="flex gap-3 justify-center flex-wrap">
            <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 bg-white text-blue-900 font-bold px-6 py-3 rounded-lg hover:bg-blue-50 transition-colors">
                <i class="fas fa-arrow-left"></i> Volver atrás
            </a>
            <a href="/admin" class="inline-flex items-center gap-2 bg-blue-600 text-white font-bold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-home"></i> Panel principal
            </a>
        </div>
        <p class="text-blue-400 text-xs mt-8">Error 500 — Elixirdorado POS v1.0</p>
    </div>
</body>
</html>
