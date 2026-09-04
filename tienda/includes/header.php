<?php
// =================================
// SESIÓN
// =================================
// Reutilizamos el mismo mecanismo de sesión que ya usa el admin
// (session_start + $_SESSION["id_usuario"], "nombre", "rol").
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =================================
// CONEXIÓN A BASE DE DATOS
// =================================
// __DIR__ hace que esta ruta funcione sin importar desde qué
// página de la tienda se incluya este header.
require_once __DIR__ . "/../../config/conexion.php";

// =================================
// URL BASE DE LA TIENDA (dinámica)
// =================================
// Se calcula a partir de la URL real de la petición, ubicando el
// segmento "/tienda" en la ruta. Así funciona sin importar en qué
// carpeta del servidor esté instalado el proyecto (localhost,
// subcarpeta, dominio final, etc.), sin quedar amarrada a una
// ruta fija como pasaba antes.
$scriptDir = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
$posTienda = strpos($scriptDir, "/tienda");

if ($posTienda !== false) {
    $base_url = substr($scriptDir, 0, $posTienda + strlen("/tienda")) . "/";
} else {
    // Respaldo por si "tienda" no aparece en la ruta (caso raro)
    $base_url = "/tienda/";
}

// =================================
// ESTADO DE SESIÓN DEL CLIENTE
// =================================
// Solo se considera "cliente logueado" si el rol es "cliente",
// para no confundir una sesión de administrador con una de tienda.
$clienteLogueado = isset($_SESSION["id_usuario"]) && ($_SESSION["rol"] ?? "") === "cliente";
$nombreCliente = $clienteLogueado ? htmlspecialchars($_SESSION["nombre"], ENT_QUOTES, "UTF-8") : "";

// =================================
// CONTADOR DEL CARRITO
// =================================
// Suma las cantidades del carrito "Activo" del cliente logueado.
// Si no hay sesión de cliente, se muestra 0 (el carrito de invitado
// se resolverá cuando construyamos el módulo de carrito).
$totalCarrito = 0;

if ($clienteLogueado) {

    $sqlCarrito = "
        SELECT COALESCE(SUM(dc.cantidad), 0) AS total
        FROM carrito c
        INNER JOIN detalle_carrito dc ON dc.id_carrito = c.id_carrito
        WHERE c.id_usuario = ? AND c.estado = 'Activo'
    ";

    $stmtCarrito = $conexion->prepare($sqlCarrito);
    $stmtCarrito->bind_param("i", $_SESSION["id_usuario"]);
    $stmtCarrito->execute();
    $resultadoCarrito = $stmtCarrito->get_result()->fetch_assoc();
    $totalCarrito = (int) $resultadoCarrito["total"];
    $stmtCarrito->close();

}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AGRANDA</title>

    <link rel="stylesheet" href="<?= $base_url ?>css/global.css">
    <script src="<?= $base_url ?>js/global.js" defer></script>

</head>

<body>

<header class="site-header">

    <div class="header-container">

        <!-- LOGO -->
        <a href="<?= $base_url ?>index.php" class="brand">
            <span class="brand-name">AGRANDA</span>
            <span class="brand-subtitle">Repuestos agrícolas de confianza</span>
        </a>


        <!-- NAVEGACIÓN PRINCIPAL -->
        <nav class="main-nav" aria-label="Navegación principal">

            <a href="<?= $base_url ?>index.php" class="nav-link">
                Inicio
            </a>

            <a href="<?= $base_url ?>productos/" class="nav-link">
                Productos
            </a>

            <a href="<?= $base_url ?>nosotros/" class="nav-link">
                Nosotros
            </a>

            <a href="<?= $base_url ?>contacto/" class="nav-link">
                Contacto
            </a>

        </nav>


        <!-- ACCIONES DEL USUARIO -->
        <div class="header-actions">

            <!-- Buscar -->
            <a href="<?= $base_url ?>productos/" 
               class="header-action"
               aria-label="Buscar productos"
               title="Buscar productos">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <line x1="16.5" y1="16.5" x2="21" y2="21"></line>
                </svg>

            </a>


            <!-- Cuenta -->
            <a href="<?= $base_url ?>cuenta/" 
               class="header-action account-action"
               aria-label="Mi cuenta"
               title="Mi cuenta">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M4 21c0-4.2 3.6-7 8-7s8 2.8 8 7"></path>
                </svg>

                <span class="action-text">
                    <?php if ($clienteLogueado): ?>
                        <strong>Hola, <?= $nombreCliente ?></strong>
                        <small>Ver mi cuenta</small>
                    <?php else: ?>
                        <strong>Mi cuenta</strong>
                        <small>Iniciar sesión</small>
                    <?php endif; ?>
                </span>

            </a>


            <!-- Carrito -->
            <a href="<?= $base_url ?>carrito/" 
               class="header-action cart-action"
               aria-label="Mi carrito"
               title="Mi carrito">

                <span class="cart-icon-container">

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="9" cy="20" r="1.5"></circle>
                        <circle cx="18" cy="20" r="1.5"></circle>
                        <path d="M3 4h2l2.2 11.5a2 2 0 0 0 2 1.5h8.5a2 2 0 0 0 1.9-1.4L21 8H7"></path>
                    </svg>

                    <span class="cart-count"><?= $totalCarrito ?></span>

                </span>

                <span class="action-text">
                    <strong>Mi carrito</strong>
                    <small><?= $totalCarrito ?> producto<?= $totalCarrito === 1 ? "" : "s" ?></small>
                </span>

            </a>

        </div>

    </div>

</header>