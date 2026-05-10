<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Elixir Dorado — Admin</title>
    @vite(['resources/css/app.css', 'resources/js/admin/main.js'])
</head>
<body class="bg-gray-100">
    <div id="admin-app"></div>
</body>
</html>
