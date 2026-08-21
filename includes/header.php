<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar conexión a la base de datos si no está definida
if (!isset($conexion)) {
    if (file_exists(__DIR__ . '/../config/conexion.php')) {
        require_once(__DIR__ . '/../config/conexion.php');
    }
}

// Cargar helpers
if (file_exists(__DIR__ . '/helpers.php')) {
    require_once(__DIR__ . '/helpers.php');
}

$configTienda = isset($conexion) ? obtenerConfiguracionTienda($conexion) : [
    'nombre_tienda' => 'AGRANDA',
    'correo_contacto' => 'contacto@agranda.com',
    'telefono_contacto' => '+57 300 000 0000'
];

$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($configTienda['nombre_tienda']); ?> | Repuestos y Maquinaria Agrícola</title>
    <meta name="description" content="Venta de repuestos, lubricantes y maquinaria agrícola de alta calidad.">
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="productos.css">
</head>
<body>

<!-- Encabezado principal de la tienda -->
<header>
    <div class="header-brand">
        <h1><a href="index.php" style="color: white; text-decoration: none;"><?php echo htmlspecialchars($configTienda['nombre_tienda']); ?></a></h1>
    </div>
    <nav>
        <a href="index.php" class="<?php echo $paginaActual === 'index.php' ? 'activo' : ''; ?>">Inicio</a>
        <a href="productos.php" class="<?php echo $paginaActual === 'productos.php' ? 'activo' : ''; ?>">Productos</a>
        <a href="carrito.php" class="carrito-link <?php echo $paginaActual === 'carrito.php' ? 'activo' : ''; ?>">
            Carrito <span class="icono-carrito">🛒<span id="cart-count">0</span></span>
        </a>
        <a href="contacto.php" class="<?php echo $paginaActual === 'contacto.php' ? 'activo' : ''; ?>">Contacto</a>
        <a href="admin/login.php" class="admin-link" title="Acceso al Panel Administrativo" target="_blank">⚙️ Admin</a>
    </nav>
</header>
