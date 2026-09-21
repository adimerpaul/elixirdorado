<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración de negocio (Elixir Dorado)
    |--------------------------------------------------------------------------
    | Variables que antes estaban hardcodeadas en rutas y vistas. Cambiar
    | desde el .env mediante NEGOCIO_IVA, NEGOCIO_TOP_PRODUCTOS, etc.
    */

    // IVA aplicado a las ventas (Bolivia: 0.13). Si tu país es distinto,
    // ajusta vía variable de entorno: NEGOCIO_IVA=0.16
    'iva' => (float) env('NEGOCIO_IVA', 0.13),

    // Top N de productos en reportes
    'top_productos' => (int) env('NEGOCIO_TOP_PRODUCTOS', 10),

    // Paginación por defecto en listados largos (cajeros)
    'por_pagina' => (int) env('NEGOCIO_POR_PAGINA', 25),

    // Zona horaria del negocio. La BD guarda en UTC; los filtros por fecha/hora
    // y los reportes se interpretan en esta zona. NEGOCIO_TZ=America/La_Paz
    'zona_horaria' => env('NEGOCIO_TZ', 'America/La_Paz'),

    // Métodos de pago aceptados
    'metodos_pago' => ['efectivo', 'tarjeta', 'qr', 'transferencia', 'credito'],

    // Roles válidos del sistema
    'roles' => [
        'super_admin' => 'Super Admin',
        'admin'       => 'Administrador',
        'gerente'     => 'Gerente',
        'cajero'      => 'Cajero',
    ],

];
