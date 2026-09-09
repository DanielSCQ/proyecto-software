-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-09-2026 a las 15:12:04
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `base de datos tienda-inventario`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atributos_producto`
--

CREATE TABLE `atributos_producto` (
  `id_atributo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `atributos_producto`
--

INSERT INTO `atributos_producto` (`id_atributo`, `nombre`, `estado`, `fecha_creacion`) VALUES
(1, 'Material', 1, '2026-09-04 03:06:12'),
(2, 'Largo', 1, '2026-09-04 03:52:55'),
(3, 'Ancho', 1, '2026-09-04 03:53:16'),
(4, 'Diametro', 1, '2026-09-04 03:53:58'),
(5, 'Tipo de pieza', 1, '2026-09-04 03:54:43'),
(6, 'Aplicacion', 1, '2026-09-04 03:55:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id_auditoria` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `tabla_afectada` varchar(100) NOT NULL,
  `id_registro` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_usuario` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carrito`
--

CREATE TABLE `carrito` (
  `id_carrito` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `total` decimal(10,2) DEFAULT 0.00,
  `estado` enum('Activo','Finalizado') DEFAULT 'Activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`, `descripcion`, `imagen`, `estado`, `fecha_creacion`) VALUES
(1, 'sistemas hidráulicos', 'repuestos y componentes del sistema hidraulico                ', 'uploads/categorias/1784731070_sigueñal.jpg', 1, '2026-07-22 14:37:50'),
(3, 'sistemas electricos', ' componentes eléctricos para maquinaria agrícola\r\n       \r\n                            ', 'uploads/categorias/1784773887_destacado4.jpg', 1, '2026-07-23 02:31:27'),
(4, 'empaques', 'los duros', 'uploads/categorias/99f6aa345d8000f0db465f5f5b4b18db.jpg', 1, '2026-08-18 14:19:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compatibilidades`
--

CREATE TABLE `compatibilidades` (
  `id_compatibilidad` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_modelo` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_tienda`
--

CREATE TABLE `configuracion_tienda` (
  `id_configuracion` int(11) NOT NULL,
  `nombre_tienda` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `correo_contacto` varchar(100) DEFAULT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `moneda` varchar(10) DEFAULT 'COP',
  `impuesto_porcentaje` decimal(5,2) DEFAULT 0.00,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos`
--

CREATE TABLE `contactos` (
  `id_contacto` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `asunto` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','leido','respondido','cerrado') DEFAULT 'pendiente',
  `respuesta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupones`
--

CREATE TABLE `cupones` (
  `id_cupon` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_descuento` enum('Porcentaje','Fijo') NOT NULL,
  `valor_descuento` decimal(10,2) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `uso_maximo` int(11) DEFAULT NULL,
  `uso_actual` int(11) DEFAULT 0,
  `monto_minimo_compra` decimal(10,2) DEFAULT 0.00,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_carrito`
--

CREATE TABLE `detalle_carrito` (
  `id_detalle_carrito` int(11) NOT NULL,
  `id_carrito` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ingreso`
--

CREATE TABLE `detalle_ingreso` (
  `id_detalle_ingreso` int(11) NOT NULL,
  `id_ingreso` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_ingreso`
--

INSERT INTO `detalle_ingreso` (`id_detalle_ingreso`, `id_ingreso`, `id_producto`, `cantidad`, `precio_compra`, `subtotal`) VALUES
(1, 1, 1, 1234, 200000.00, 99999999.99),
(2, 2, 2, 300, 37770.00, 11331000.00),
(3, 3, 5, 2111, 6000.00, 12666000.00),
(4, 4, 1, 3000, 30000.00, 90000000.00),
(5, 5, 1, 3000, 30000.00, 90000000.00),
(6, 6, 6, 50, 2000.00, 100000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id_detalle_pedido` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id_detalle_pedido`, `id_pedido`, `id_producto`, `cantidad`, `precio_unitario`, `descuento`, `subtotal`) VALUES
(1, 1, 5, 3, 12132.00, 0.00, 36396.00),
(2, 1, 4, 1, 121321.00, 0.00, 121321.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direcciones`
--

CREATE TABLE `direcciones` (
  `id_direccion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `receptor` varchar(200) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `municipio` varchar(100) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `referencia` text DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 0,
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `direcciones`
--

INSERT INTO `direcciones` (`id_direccion`, `id_usuario`, `nombre`, `telefono`, `receptor`, `direccion`, `barrio`, `municipio`, `departamento`, `referencia`, `principal`, `estado`) VALUES
(1, 2, 'casa de los quesada', '3209007970', 'Daniel santiago cortes quesada', 'carrera 9 N°13-61', 'ospina perez sector 1', 'PURIFICACIÓN', 'Tolima', 'casa verde al lado de los otavo', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_pedido`
--

CREATE TABLE `estado_pedido` (
  `id_estado` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estado_pedido`
--

INSERT INTO `estado_pedido` (`id_estado`, `nombre`, `descripcion`, `orden`) VALUES
(1, 'Pendiente', 'Pedido recibido y pendiente de procesamiento', 1),
(2, 'En proceso', 'El pedido está siendo preparado', 2),
(3, 'Enviado', 'El pedido fue enviado al cliente', 3),
(4, 'Entregado', 'El pedido fue entregado correctamente', 4),
(5, 'Cancelado', 'El pedido fue cancelado', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `id_favorito` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `favoritos`
--

INSERT INTO `favoritos` (`id_favorito`, `id_usuario`, `id_producto`, `fecha_agregado`) VALUES
(8, 2, 4, '2026-09-07 03:56:38'),
(12, 2, 5, '2026-09-08 02:35:03'),
(13, 2, 1, '2026-09-08 02:35:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_estado_pedido`
--

CREATE TABLE `historial_estado_pedido` (
  `id_historial` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `ubicacion` varchar(150) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_estado_pedido`
--

INSERT INTO `historial_estado_pedido` (`id_historial`, `id_pedido`, `id_estado`, `ubicacion`, `descripcion`, `fecha`) VALUES
(1, 1, 1, NULL, 'Pedido creado y pendiente de procesamiento.', '2026-09-08 12:16:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `imagenes_producto`
--

CREATE TABLE `imagenes_producto` (
  `id_imagen` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `ruta_imagen` varchar(255) NOT NULL,
  `principal` tinyint(1) DEFAULT 0,
  `orden` int(11) DEFAULT 1,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `imagenes_producto`
--

INSERT INTO `imagenes_producto` (`id_imagen`, `id_producto`, `ruta_imagen`, `principal`, `orden`, `fecha_subida`, `estado`) VALUES
(1, 1, 'uploads/productos/1785535100_destacado 3.webp', 1, 1, '2026-07-31 21:58:20', 1),
(2, 2, 'uploads/productos/1785543035_destacado2.webp', 1, 1, '2026-08-01 00:10:35', 1),
(3, 4, 'uploads/productos/1785849579_destacado4.jpg', 1, 1, '2026-08-04 13:19:39', 1),
(4, 5, 'uploads/productos/1786666092_WhatsApp Image 2026-08-02 at 3.21.27 PM.jpeg', 1, 1, '2026-08-14 00:08:12', 1),
(5, 6, 'uploads/productos/10e5f157a433e85da0c36f84ccdb338f.jpg', 1, 1, '2026-08-26 01:36:42', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos_inventario`
--

CREATE TABLE `ingresos_inventario` (
  `id_ingreso` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `documento` varchar(100) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `total_compra` decimal(10,2) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `estado` enum('Pendiente','Recibido','Cancelado') DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ingresos_inventario`
--

INSERT INTO `ingresos_inventario` (`id_ingreso`, `id_proveedor`, `fecha`, `documento`, `referencia`, `total_compra`, `id_usuario`, `estado`) VALUES
(1, 1, '2026-08-04', '888266109', 'OC-028373', 99999999.99, 1, 'Recibido'),
(2, 1, '2026-08-11', '52914101', 'OC-028373', 11331000.00, 1, 'Recibido'),
(3, 11, '2026-08-28', '66vsu2y2', 'OC-028373', 12666000.00, 1, 'Recibido'),
(4, 11, '2026-08-28', '88710003661', '00123331', 90000000.00, 1, 'Recibido'),
(5, 3, '2026-08-28', '14co-42466', '2244ccc', 90000000.00, 1, 'Recibido'),
(6, 1, '2026-08-28', '222222', '5555', 100000.00, 1, 'Recibido');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intentos_pago`
--

CREATE TABLE `intentos_pago` (
  `id_intento` int(11) NOT NULL,
  `id_pago` int(11) NOT NULL,
  `numero_intento` int(11) DEFAULT 1,
  `estado` enum('Pendiente','Exitoso','Fallido','Cancelado') DEFAULT 'Pendiente',
  `respuesta_pasarela` text DEFAULT NULL,
  `codigo_error` varchar(100) DEFAULT NULL,
  `fecha_intento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id_inventario` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `stock_actual` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 0,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id_inventario`, `id_producto`, `stock_actual`, `stock_minimo`, `fecha_actualizacion`) VALUES
(1, 1, 6000, 50, '2026-08-29 01:10:32'),
(2, 2, 421, 20, '2026-08-29 04:35:17'),
(4, 4, 199, 20, '2026-09-08 12:16:33'),
(5, 5, 8552, 20, '2026-09-08 12:16:33'),
(6, 6, 1000050, 122, '2026-08-29 04:35:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marcas`
--

CREATE TABLE `marcas` (
  `id_marca` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `marcas`
--

INSERT INTO `marcas` (`id_marca`, `nombre`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(1, 'JOHN DEERE ', 'repuestos duros ', 1, '2026-07-23 03:43:12'),
(2, 'CATERPILLAR', 'Maquinaria y equipo de minería especializada', 1, '2026-08-28 02:09:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelos`
--

CREATE TABLE `modelos` (
  `id_modelo` int(11) NOT NULL,
  `id_marca` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `anio_inicio` year(4) DEFAULT NULL,
  `anio_fin` year(4) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id_movimiento` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_ingreso` int(11) DEFAULT NULL,
  `tipo` enum('Entrada','Salida','Ajuste') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` text NOT NULL,
  `observacion` text DEFAULT NULL,
  `id_pedido` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id_movimiento`, `id_producto`, `id_ingreso`, `tipo`, `cantidad`, `motivo`, `observacion`, `id_pedido`, `id_proveedor`, `id_usuario`, `fecha`) VALUES
(1, 4, NULL, 'Ajuste', -100, 'Actualización manual desde el panel', '', NULL, NULL, 1, '2026-08-01 18:25:04'),
(2, 1, NULL, 'Entrada', 1234, 'Ingreso de inventario', 'chimba', NULL, 1, 1, '2026-08-04 14:28:35'),
(3, 2, NULL, 'Entrada', 300, 'Ingreso de inventario', 'llegaron nuevos', NULL, 1, 1, '2026-08-11 11:58:19'),
(4, 6, NULL, 'Ajuste', -6000, 'Actualización manual desde el panel', '', NULL, NULL, 1, '2026-08-28 22:34:00'),
(5, 6, NULL, 'Ajuste', 996000, 'Actualización manual desde el panel', '', NULL, NULL, 1, '2026-08-28 22:34:30'),
(6, 5, NULL, 'Entrada', 2111, 'Ingreso de inventario', 'duros', NULL, 11, 1, '2026-08-28 22:48:09'),
(7, 1, NULL, 'Entrada', 2147483647, 'Ingreso de inventario', 'tftfft', NULL, 11, 1, '2026-08-28 23:04:56'),
(8, 1, NULL, 'Ajuste', -2147480647, 'Modificación de ingreso de inventario', 'Cambio de cantidad del ingreso #4. Cantidad anterior: 2147483647. Nueva cantidad: 3000', NULL, 11, 1, '2026-08-29 00:30:13'),
(9, 4, NULL, 'Entrada', 4000, 'Ingreso de inventario', 'ninguna', NULL, 3, 1, '2026-08-29 01:07:48'),
(10, 4, NULL, 'Ajuste', -1000, 'Modificación de ingreso de inventario', 'Cambio de cantidad del ingreso #5. Cantidad anterior: 4000. Nueva cantidad: 3000. Observación: error en el precio y cantidad', NULL, 3, 1, '2026-08-29 01:09:19'),
(11, 4, NULL, 'Ajuste', -3000, 'Modificación de ingreso de inventario', 'Se retiraron 3000 unidades debido al cambio de producto del ingreso #5. Observación: prueba', NULL, NULL, 1, '2026-08-29 01:10:32'),
(12, 1, NULL, 'Ajuste', 3000, 'Modificación de ingreso de inventario', 'Se agregaron 3000 unidades debido al cambio de producto del ingreso #5. Observación: prueba', NULL, 3, 1, '2026-08-29 01:10:32'),
(13, 2, 6, 'Entrada', 34, 'Ingreso de inventario', 'si aplica', NULL, 1, 1, '2026-08-29 04:29:15'),
(14, 2, 6, 'Ajuste', 16, 'Modificación de ingreso de inventario', 'Cambio de cantidad del ingreso #6. Cantidad anterior: 34. Nueva cantidad: 50. Observación: nuevos', NULL, 1, 1, '2026-08-29 04:30:57'),
(15, 2, 6, 'Ajuste', -50, 'Modificación de ingreso de inventario', 'Se retiraron 50 unidades debido al cambio de producto del ingreso #6. Observación: error en el tipo de producto', NULL, NULL, 1, '2026-08-29 04:35:17'),
(16, 6, 6, 'Ajuste', 50, 'Modificación de ingreso de inventario', 'Se agregaron 50 unidades debido al cambio de producto del ingreso #6. Observación: error en el tipo de producto', NULL, 1, 1, '2026-08-29 04:35:17'),
(17, 5, NULL, 'Salida', -3, 'Venta de producto', 'Salida de inventario por pedido #1', 1, NULL, 2, '2026-09-08 12:16:33'),
(18, 4, NULL, 'Salida', -1, 'Venta de producto', 'Salida de inventario por pedido #1', 1, NULL, 2, '2026-09-08 12:16:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `mensaje` text NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_lectura` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `moneda` varchar(10) DEFAULT 'COP',
  `metodo_pago` varchar(50) NOT NULL,
  `estado` enum('Pendiente','Aprobado','Rechazado','Reembolsado') DEFAULT 'Pendiente',
  `referencia_unica` varchar(100) NOT NULL,
  `endpoint_key` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_direccion` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `token_checkout` varchar(64) DEFAULT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `id_usuario`, `id_direccion`, `id_estado`, `total`, `metodo_pago`, `token_checkout`, `fecha_pedido`, `fecha_actualizacion`) VALUES
(1, 2, 1, 1, 157717.00, 'Contra entrega', '3757d3052218c00ee6c5d7fbb2965c75ad3eebad512027ddc9cd32bccd26df30', '2026-09-08 12:16:33', '2026-09-08 12:16:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_marca` int(11) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `codigo_producto` varchar(50) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `peso` decimal(8,2) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `destacado` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `id_categoria`, `id_marca`, `nombre`, `descripcion`, `codigo_producto`, `precio`, `peso`, `estado`, `destacado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 1, 'sigueñal', 'muy util', '223878', 120000.00, 13.00, 1, 1, '2026-07-31 21:58:20', '2026-07-31 21:58:20'),
(2, 3, 1, 'cables', 'muy buenos', '112223', 55000.00, 11.85, 1, 1, '2026-08-01 00:10:35', '2026-08-04 14:32:45'),
(4, 3, 1, 'sistemas electricos', 'dggdfgergear', 'pps7788', 121321.00, 354.00, 1, 1, '2026-08-01 17:49:53', '2026-08-04 14:32:35'),
(5, 4, 1, 'coco', 'bueno', '342f3r42', 12132.00, 354.00, 1, 1, '2026-08-14 00:08:12', '2026-09-02 15:30:06'),
(6, 3, 1, 'tornillo', 'buenos y baratos', '8782gvb72', 500.00, 0.65, 1, 0, '2026-08-26 01:36:42', '2026-08-29 04:34:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_atributo`
--

CREATE TABLE `producto_atributo` (
  `id_producto_atributo` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_atributo` int(11) NOT NULL,
  `valor` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto_atributo`
--

INSERT INTO `producto_atributo` (`id_producto_atributo`, `id_producto`, `id_atributo`, `valor`) VALUES
(1, 6, 4, '2.35 mm'),
(2, 6, 2, '5 cm'),
(3, 6, 1, 'Acero inoxidable');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `id_promocion` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('Porcentaje','Fijo') NOT NULL,
  `valor_descuento` decimal(10,2) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `promociones`
--

INSERT INTO `promociones` (`id_promocion`, `nombre`, `descripcion`, `tipo`, `valor_descuento`, `fecha_inicio`, `fecha_fin`, `estado`, `fecha_creacion`) VALUES
(1, 'super descuentos de temporada', 'las mejores ofertas del mercado', 'Porcentaje', 35.00, '2026-08-12', '2026-11-12', 0, '2026-08-12 20:43:04'),
(2, 'descuentos navideños', 'descuentos en todo lo navideño', 'Fijo', 30000.00, '2026-08-11', '2026-12-22', 0, '2026-08-12 21:04:20'),
(3, 'desdcuentos de los brujos', 'solo este mes de octubre', 'Fijo', 30000.00, '2026-08-12', '2026-12-12', 1, '2026-08-12 21:16:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promocion_categoria`
--

CREATE TABLE `promocion_categoria` (
  `id_promocion_categoria` int(11) NOT NULL,
  `id_promocion` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promocion_producto`
--

CREATE TABLE `promocion_producto` (
  `id_promocion_producto` int(11) NOT NULL,
  `id_promocion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `promocion_producto`
--

INSERT INTO `promocion_producto` (`id_promocion_producto`, `id_promocion`, `id_producto`, `fecha_asignacion`) VALUES
(6, 1, 1, '2026-08-12 21:54:47'),
(38, 2, 2, '2026-08-28 13:26:56'),
(39, 3, 2, '2026-08-28 13:27:31'),
(40, 3, 1, '2026-08-28 13:27:31'),
(41, 3, 4, '2026-08-28 13:27:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `nit` varchar(20) NOT NULL,
  `telefono` varchar(25) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `contacto_principal` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id_proveedor`, `nombre`, `nit`, `telefono`, `correo`, `direccion`, `contacto_principal`, `estado`, `fecha_creacion`) VALUES
(1, 'yo', '1112121221', '3209007970', 'danielscq08@gmail.com', 'carrera9 #13-61', '3108094559', 1, '2026-07-23 15:00:34'),
(3, 'AgroRepuestos tolima', '901234567-8', '3', 'dorq138@gmail.com', '', 'efefefwefewfewf', 1, '2026-08-28 02:59:19'),
(11, 'reston', '72663413', '3001678131', 'danielscq08@gmail.com', 'carrera 9 N°13-61', 'don pablo', 1, '2026-08-28 04:09:31'),
(12, 'elvert', '7714291238129', '82723734', 'danielscq08@gmail.com', 'carrera 9 N°13-61', 'don pablo', 0, '2026-08-28 13:14:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor_producto`
--

CREATE TABLE `proveedor_producto` (
  `id_proveedor_producto` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `codigo_proveedor` varchar(50) DEFAULT NULL,
  `tiempo_entrega` int(11) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor_producto`
--

INSERT INTO `proveedor_producto` (`id_proveedor_producto`, `id_proveedor`, `id_producto`, `precio_compra`, `codigo_proveedor`, `tiempo_entrega`, `estado`, `fecha_creacion`) VALUES
(1, 1, 1, 100000.00, NULL, NULL, 1, '2026-07-31 21:58:20'),
(2, 1, 2, 40000.00, NULL, NULL, 1, '2026-08-01 00:10:35'),
(4, 1, 4, 213231.00, NULL, NULL, 1, '2026-08-01 17:49:53'),
(5, 1, 5, 2000.03, NULL, NULL, 1, '2026-08-14 00:08:12'),
(6, 1, 6, 500.00, '121224', NULL, 1, '2026-08-26 01:36:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reembolsos`
--

CREATE TABLE `reembolsos` (
  `id_reembolso` int(11) NOT NULL,
  `id_pago` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `motivo` text NOT NULL,
  `estado` enum('Solicitado','Aprobado','Rechazado') DEFAULT 'Solicitado',
  `metodo_reembolso` varchar(50) DEFAULT NULL,
  `referencia_reembolso` varchar(100) DEFAULT NULL,
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenas`
--

CREATE TABLE `resenas` (
  `id_resena` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `calificacion` tinyint(4) NOT NULL,
  `comentario` text DEFAULT NULL,
  `compra_verificada` tinyint(1) DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `uso_cupones`
--

CREATE TABLE `uso_cupones` (
  `id_uso` int(11) NOT NULL,
  `id_cupon` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `descuento_aplicado` decimal(10,2) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `email_verificado` tinyint(1) NOT NULL DEFAULT 0,
  `token_verificacion_hash` varchar(64) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `clave` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `rol` enum('cliente','administrador') DEFAULT 'cliente',
  `estado` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `correo`, `google_id`, `email_verificado`, `token_verificacion_hash`, `token_expira`, `clave`, `telefono`, `rol`, `estado`, `fecha_registro`) VALUES
(1, 'daniel santiago', 'cortes quesada', 'danielscq08@gmail.com', NULL, 0, NULL, NULL, '123456', '3209007970', 'administrador', 1, '2026-07-17 15:11:25'),
(2, 'Daniel santiago', 'cortes quesada', 'dorq138@gmail.com', NULL, 0, '19ee0e3bcc29358a62de0d83b7b24b7b4f8dba2862b13973257032825c1a9829', '2026-09-06 19:50:13', '$2y$10$e6zu33MKfZJoWqFYpOa4Cu6X.NnidK7aTW8T6Es61Dvraljkiotda', '3209007970', 'cliente', 1, '2026-09-06 23:50:13'),
(3, 'FRANCISCO', 'JAVIER', 'franciscojavieravilareyes2009@gmail.com', NULL, 0, '371fb51291a4eb7bfe9a6991d4d35f6ab49f067df314637f7f7f859b4ff35e1e', '2026-09-06 20:24:09', '$2y$10$Pdxxou05sBxjkLg.rLmkHeMDtUP.2KnNzrpZ/oLfTa21TZWoO9Fbu', '3151819241', 'cliente', 1, '2026-09-07 00:24:09');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `atributos_producto`
--
ALTER TABLE `atributos_producto`
  ADD PRIMARY KEY (`id_atributo`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD PRIMARY KEY (`id_carrito`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `compatibilidades`
--
ALTER TABLE `compatibilidades`
  ADD PRIMARY KEY (`id_compatibilidad`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_modelo` (`id_modelo`);

--
-- Indices de la tabla `configuracion_tienda`
--
ALTER TABLE `configuracion_tienda`
  ADD PRIMARY KEY (`id_configuracion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `contactos`
--
ALTER TABLE `contactos`
  ADD PRIMARY KEY (`id_contacto`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `cupones`
--
ALTER TABLE `cupones`
  ADD PRIMARY KEY (`id_cupon`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  ADD PRIMARY KEY (`id_detalle_carrito`),
  ADD KEY `id_carrito` (`id_carrito`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  ADD PRIMARY KEY (`id_detalle_ingreso`),
  ADD KEY `id_ingreso` (`id_ingreso`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id_detalle_pedido`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD PRIMARY KEY (`id_direccion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `estado_pedido`
--
ALTER TABLE `estado_pedido`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id_favorito`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`,`id_producto`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `historial_estado_pedido`
--
ALTER TABLE `historial_estado_pedido`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `imagenes_producto`
--
ALTER TABLE `imagenes_producto`
  ADD PRIMARY KEY (`id_imagen`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `ingresos_inventario`
--
ALTER TABLE `ingresos_inventario`
  ADD PRIMARY KEY (`id_ingreso`),
  ADD KEY `id_proveedor` (`id_proveedor`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `intentos_pago`
--
ALTER TABLE `intentos_pago`
  ADD PRIMARY KEY (`id_intento`),
  ADD KEY `id_pago` (`id_pago`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id_inventario`),
  ADD UNIQUE KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD PRIMARY KEY (`id_marca`);

--
-- Indices de la tabla `modelos`
--
ALTER TABLE `modelos`
  ADD PRIMARY KEY (`id_modelo`),
  ADD KEY `id_marca` (`id_marca`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_proveedor` (`id_proveedor`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `fk_movimiento_ingreso` (`id_ingreso`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD UNIQUE KEY `referencia_unica` (`referencia_unica`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD UNIQUE KEY `uk_pedidos_token_checkout` (`token_checkout`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_direccion` (`id_direccion`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD UNIQUE KEY `codigo_producto` (`codigo_producto`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_marca` (`id_marca`);

--
-- Indices de la tabla `producto_atributo`
--
ALTER TABLE `producto_atributo`
  ADD PRIMARY KEY (`id_producto_atributo`),
  ADD UNIQUE KEY `id_producto` (`id_producto`,`id_atributo`),
  ADD KEY `id_atributo` (`id_atributo`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id_promocion`);

--
-- Indices de la tabla `promocion_categoria`
--
ALTER TABLE `promocion_categoria`
  ADD PRIMARY KEY (`id_promocion_categoria`),
  ADD UNIQUE KEY `id_promocion` (`id_promocion`,`id_categoria`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `promocion_producto`
--
ALTER TABLE `promocion_producto`
  ADD PRIMARY KEY (`id_promocion_producto`),
  ADD KEY `id_promocion` (`id_promocion`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`),
  ADD UNIQUE KEY `nit` (`nit`);

--
-- Indices de la tabla `proveedor_producto`
--
ALTER TABLE `proveedor_producto`
  ADD PRIMARY KEY (`id_proveedor_producto`),
  ADD UNIQUE KEY `id_proveedor` (`id_proveedor`,`id_producto`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `reembolsos`
--
ALTER TABLE `reembolsos`
  ADD PRIMARY KEY (`id_reembolso`),
  ADD UNIQUE KEY `referencia_reembolso` (`referencia_reembolso`),
  ADD KEY `id_pago` (`id_pago`);

--
-- Indices de la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD PRIMARY KEY (`id_resena`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `uso_cupones`
--
ALTER TABLE `uso_cupones`
  ADD PRIMARY KEY (`id_uso`),
  ADD KEY `id_cupon` (`id_cupon`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `atributos_producto`
--
ALTER TABLE `atributos_producto`
  MODIFY `id_atributo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id_auditoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carrito`
--
ALTER TABLE `carrito`
  MODIFY `id_carrito` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `compatibilidades`
--
ALTER TABLE `compatibilidades`
  MODIFY `id_compatibilidad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_tienda`
--
ALTER TABLE `configuracion_tienda`
  MODIFY `id_configuracion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contactos`
--
ALTER TABLE `contactos`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cupones`
--
ALTER TABLE `cupones`
  MODIFY `id_cupon` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  MODIFY `id_detalle_carrito` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  MODIFY `id_detalle_ingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id_detalle_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `direcciones`
--
ALTER TABLE `direcciones`
  MODIFY `id_direccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `estado_pedido`
--
ALTER TABLE `estado_pedido`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id_favorito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `historial_estado_pedido`
--
ALTER TABLE `historial_estado_pedido`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `imagenes_producto`
--
ALTER TABLE `imagenes_producto`
  MODIFY `id_imagen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `ingresos_inventario`
--
ALTER TABLE `ingresos_inventario`
  MODIFY `id_ingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `intentos_pago`
--
ALTER TABLE `intentos_pago`
  MODIFY `id_intento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id_inventario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `marcas`
--
ALTER TABLE `marcas`
  MODIFY `id_marca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `modelos`
--
ALTER TABLE `modelos`
  MODIFY `id_modelo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `producto_atributo`
--
ALTER TABLE `producto_atributo`
  MODIFY `id_producto_atributo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id_promocion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `promocion_categoria`
--
ALTER TABLE `promocion_categoria`
  MODIFY `id_promocion_categoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `promocion_producto`
--
ALTER TABLE `promocion_producto`
  MODIFY `id_promocion_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `proveedor_producto`
--
ALTER TABLE `proveedor_producto`
  MODIFY `id_proveedor_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `reembolsos`
--
ALTER TABLE `reembolsos`
  MODIFY `id_reembolso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resenas`
--
ALTER TABLE `resenas`
  MODIFY `id_resena` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `uso_cupones`
--
ALTER TABLE `uso_cupones`
  MODIFY `id_uso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `carrito`
--
ALTER TABLE `carrito`
  ADD CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `compatibilidades`
--
ALTER TABLE `compatibilidades`
  ADD CONSTRAINT `compatibilidades_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `compatibilidades_ibfk_2` FOREIGN KEY (`id_modelo`) REFERENCES `modelos` (`id_modelo`);

--
-- Filtros para la tabla `configuracion_tienda`
--
ALTER TABLE `configuracion_tienda`
  ADD CONSTRAINT `configuracion_tienda_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `contactos`
--
ALTER TABLE `contactos`
  ADD CONSTRAINT `contactos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  ADD CONSTRAINT `detalle_carrito_ibfk_1` FOREIGN KEY (`id_carrito`) REFERENCES `carrito` (`id_carrito`),
  ADD CONSTRAINT `detalle_carrito_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  ADD CONSTRAINT `detalle_ingreso_ibfk_1` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos_inventario` (`id_ingreso`),
  ADD CONSTRAINT `detalle_ingreso_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `detalle_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`),
  ADD CONSTRAINT `detalle_pedido_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `direcciones`
--
ALTER TABLE `direcciones`
  ADD CONSTRAINT `direcciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `historial_estado_pedido`
--
ALTER TABLE `historial_estado_pedido`
  ADD CONSTRAINT `historial_estado_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`),
  ADD CONSTRAINT `historial_estado_pedido_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado_pedido` (`id_estado`);

--
-- Filtros para la tabla `imagenes_producto`
--
ALTER TABLE `imagenes_producto`
  ADD CONSTRAINT `imagenes_producto_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `ingresos_inventario`
--
ALTER TABLE `ingresos_inventario`
  ADD CONSTRAINT `ingresos_inventario_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  ADD CONSTRAINT `ingresos_inventario_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `intentos_pago`
--
ALTER TABLE `intentos_pago`
  ADD CONSTRAINT `intentos_pago_ibfk_1` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`);

--
-- Filtros para la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `modelos`
--
ALTER TABLE `modelos`
  ADD CONSTRAINT `modelos_ibfk_1` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id_marca`);

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `fk_movimiento_ingreso` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos_inventario` (`id_ingreso`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_3` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_4` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`id_direccion`) REFERENCES `direcciones` (`id_direccion`),
  ADD CONSTRAINT `pedidos_ibfk_3` FOREIGN KEY (`id_estado`) REFERENCES `estado_pedido` (`id_estado`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`),
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id_marca`);

--
-- Filtros para la tabla `producto_atributo`
--
ALTER TABLE `producto_atributo`
  ADD CONSTRAINT `producto_atributo_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `producto_atributo_ibfk_2` FOREIGN KEY (`id_atributo`) REFERENCES `atributos_producto` (`id_atributo`);

--
-- Filtros para la tabla `promocion_categoria`
--
ALTER TABLE `promocion_categoria`
  ADD CONSTRAINT `promocion_categoria_ibfk_1` FOREIGN KEY (`id_promocion`) REFERENCES `promociones` (`id_promocion`),
  ADD CONSTRAINT `promocion_categoria_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`);

--
-- Filtros para la tabla `promocion_producto`
--
ALTER TABLE `promocion_producto`
  ADD CONSTRAINT `promocion_producto_ibfk_1` FOREIGN KEY (`id_promocion`) REFERENCES `promociones` (`id_promocion`),
  ADD CONSTRAINT `promocion_producto_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `proveedor_producto`
--
ALTER TABLE `proveedor_producto`
  ADD CONSTRAINT `proveedor_producto_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  ADD CONSTRAINT `proveedor_producto_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `reembolsos`
--
ALTER TABLE `reembolsos`
  ADD CONSTRAINT `reembolsos_ibfk_1` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`);

--
-- Filtros para la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD CONSTRAINT `resenas_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `resenas_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `uso_cupones`
--
ALTER TABLE `uso_cupones`
  ADD CONSTRAINT `uso_cupones_ibfk_1` FOREIGN KEY (`id_cupon`) REFERENCES `cupones` (`id_cupon`),
  ADD CONSTRAINT `uso_cupones_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `uso_cupones_ibfk_3` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
