<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema en mantenimiento — Elixirdorado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #1e3a5f 0%, #152b47 100%); min-height: 100vh; }
        @keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
        .spinning { animation: spin 3s linear infinite; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="text-center max-w-md">
        <div class="text-7xl mb-6 spinning inline-block">⚙️</div>
        <h1 class="text-2xl font-black text-white mb-3">Sistema en mantenimiento</h1>
        <p class="text-blue-200 mb-6 text-sm leading-relaxed">
            El sistema Elixirdorado está siendo actualizado.<br>
            Regresa en unos minutos.
        </p>
        <div class="bg-white bg-opacity-10 rounded-lg p-5 mb-6">
            <div class="flex items-center gap-3 text-blue-100 text-sm">
                <div class="w-3 h-3 bg-yellow-400 rounded-full animate-pulse"></div>
                <span>Mantenimiento en progreso...</span>
            </div>
        </div>
        <button onclick="location.reload()" class="inline-flex items-center gap-2 bg-blue-600 text-white font-bold px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-redo"></i> Intentar de nuevo
        </button>
        <p class="text-blue-400 text-xs mt-8">Elixirdorado POS v1.0</p>
    </div>
</body>
</html>
