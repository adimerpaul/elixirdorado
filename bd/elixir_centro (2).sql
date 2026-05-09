-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-05-2026 a las 02:11:58
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `elixir_centro`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 'Whisky', '2026-04-10 04:00:00', '2026-04-10 04:00:00'),
(2, 'Vodka', '2026-04-10 04:00:00', '2026-04-10 04:00:00'),
(3, 'Cerveza', '2026-04-10 04:00:00', '2026-04-10 04:00:00'),
(4, 'Refresco', '2026-04-10 04:00:00', '2026-04-10 04:00:00'),
(5, 'Ron', '2026-04-10 04:00:00', '2026-04-10 04:00:00'),
(6, 'Vino', '2026-04-10 04:00:00', '2026-04-10 04:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `rfc_nit` varchar(255) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `limite_credito` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_credito` decimal(10,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `telefono`, `email`, `rfc_nit`, `direccion`, `limite_credito`, `saldo_credito`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'mau', '12345678', 'mauriciogerardo987654321@gmail.com', '13314425', '75875079', 1000.00, 0.00, 1, '2026-04-18 20:07:42', '2026-04-18 20:07:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `venta_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 220.00, 220.00, '2026-04-10 14:00:00', '2026-04-10 14:00:00'),
(2, 2, 1, 1, 220.00, 220.00, '2026-04-10 14:30:00', '2026-04-10 14:30:00'),
(3, 3, 1, 1, 220.00, 220.00, '2026-04-18 20:09:50', '2026-04-18 20:09:50'),
(4, 3, 2, 1, 400.00, 400.00, '2026-04-18 20:09:50', '2026-04-18 20:09:50'),
(5, 3, 9, 2, 15.00, 30.00, '2026-04-18 20:09:50', '2026-04-18 20:09:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_04_10_033204_create_categorias_table', 1),
(2, '2026_04_10_033211_create_productos_table', 1),
(3, '2026_04_10_033217_create_usuarios_table', 1),
(4, '2026_04_10_033223_create_ventas_table', 1),
(5, '2026_04_10_033229_create_detalle_ventas_table', 1),
(6, '2026_04_10_120000_create_clientes_table', 1),
(7, '2026_04_18_000001_add_imagen_to_productos_table', 2),
(8, '2026_05_07_235327_fix_ventas_usuario_id_foreign', 2),
(9, '2026_05_07_235836_add_activo_to_productos_table', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo_barras` varchar(255) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `categoria_id` bigint(20) UNSIGNED DEFAULT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock_actual` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 5,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo_barras`, `nombre`, `descripcion`, `imagen`, `categoria_id`, `precio_compra`, `precio_venta`, `stock_actual`, `stock_minimo`, `activo`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Absolut Vodka', 'Vodka premium importado 750ml', 'productos/centro/b76bqrTVc40BgWDLDAHoaMi1gSSWdTU91fe58f5T.webp', 2, 154.00, 220.00, 19, 0, 0, '2026-04-10 04:00:00', '2026-05-08 04:03:36'),
(2, NULL, 'Buchanan\'s 12 Años', 'Whisky escocés 12 años 750ml', NULL, 1, 280.00, 400.00, 7, 3, 0, '2026-04-10 04:00:00', '2026-05-08 04:03:42'),
(3, NULL, 'Coca Cola 2L', 'Refresco de cola botella 2 litros', NULL, 4, 8.40, 12.00, 50, 10, 0, '2026-04-10 04:00:00', '2026-05-08 04:03:48'),
(4, NULL, 'Huari', 'Cerveza boliviana lata 350ml', NULL, 3, 12.60, 18.00, 80, 15, 0, '2026-04-10 04:00:00', '2026-05-08 04:03:53'),
(5, NULL, 'Jack Daniel\'s', 'Tennessee Whiskey 750ml', NULL, 1, 203.00, 290.00, 12, 5, 0, '2026-04-10 04:00:00', '2026-05-08 04:02:43'),
(6, NULL, 'Johnnie Walker Black Label 200ml', 'Whisky escocés botella 200ml', NULL, 1, 154.00, 220.00, 8, 5, 0, '2026-04-10 04:00:00', '2026-05-08 04:02:45'),
(7, NULL, 'Johnnie Walker Black Label 750ml', 'Whisky escocés botella 750ml', NULL, 1, 245.00, 350.00, 10, 5, 0, '2026-04-10 04:00:00', '2026-05-08 04:02:47'),
(8, NULL, 'Johnnie Walker Red Label', 'Whisky escocés Red Label 750ml', NULL, 1, 126.00, 180.00, 15, 5, 0, '2026-04-10 04:00:00', '2026-05-08 04:02:49'),
(9, NULL, 'Paceña', 'Cerveza boliviana botella 620ml', NULL, 3, 10.50, 15.00, 98, 20, 0, '2026-04-10 04:00:00', '2026-05-08 04:02:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','cajero','gerente') NOT NULL DEFAULT 'cajero',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `name`, `email`, `password`, `rol`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin@elixirdorado.com', '$2y$12$ruo2vZEdoWL5UGBprKF8bu/JlA7uTbPY5cpVLiv3pqhYhO4Y/Unj6', 'admin', '2026-04-10 04:00:00', '2026-04-10 04:00:00'),
(2, 'Cajero Principal', 'cajero@centro.elixirdorado.com', '$2y$12$ruo2vZEdoWL5UGBprKF8bu/JlA7uTbPY5cpVLiv3pqhYhO4Y/Unj6', 'cajero', '2026-04-10 04:00:00', '2026-04-10 04:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `folio` varchar(255) NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(255) NOT NULL,
  `estado` varchar(255) NOT NULL DEFAULT 'completada',
  `fecha_venta` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `folio`, `usuario_id`, `subtotal`, `iva`, `total`, `metodo_pago`, `estado`, `fecha_venta`, `created_at`, `updated_at`) VALUES
(1, 'VENTA-20260410-7E9CF0', 1, 194.69, 25.31, 220.00, 'transferencia', 'completada', '2026-04-10 14:00:00', '2026-04-10 14:00:00', '2026-04-10 14:00:00'),
(2, 'VENTA-20260410-CD7E84', 1, 194.69, 25.31, 220.00, 'efectivo', 'completada', '2026-04-10 14:30:00', '2026-04-10 14:30:00', '2026-04-10 14:30:00'),
(3, 'VENTA-20260418-EE7EC6', 1, 575.22, 74.78, 650.00, 'efectivo', 'completada', '2026-04-18 20:09:50', '2026-04-18 20:09:50', '2026-04-18 20:09:50');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `detalle_ventas_venta_id_foreign` (`venta_id`),
  ADD KEY `detalle_ventas_producto_id_foreign` (`producto_id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `productos_codigo_barras_unique` (`codigo_barras`),
  ADD KEY `productos_categoria_id_foreign` (`categoria_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuarios_email_unique` (`email`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ventas_folio_unique` (`folio`),
  ADD KEY `ventas_usuario_id_foreign` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `detalle_ventas_venta_id_foreign` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
