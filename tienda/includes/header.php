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
// TOKENS CSRF DE CUENTA
// =================================

if (
    !isset($_SESSION["csrf_login"]) ||
    !is_string($_SESSION["csrf_login"])
) {
    $_SESSION["csrf_login"] = bin2hex(random_bytes(32));
}

if (
    !isset($_SESSION["csrf_registro"]) ||
    !is_string($_SESSION["csrf_registro"])
) {
    $_SESSION["csrf_registro"] = bin2hex(random_bytes(32));
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
//
// El carrito actual se guarda temporalmente
// en la sesión.
//
// Esto permite que un visitante agregue
// productos incluso antes de iniciar sesión.
//

$totalCarrito = 0;

if (
    isset($_SESSION["carrito"]) &&
    is_array($_SESSION["carrito"])
) {

    foreach ($_SESSION["carrito"] as $item) {

        $cantidad =
            (int) ($item["cantidad"] ?? 0);

        if ($cantidad > 0) {
            $totalCarrito += $cantidad;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AGRANDA</title>

    <link rel="stylesheet" href="<?= $base_url ?>css/global.css">
    <link rel="stylesheet" href="<?= $base_url ?>css/cuenta.css">

    <script src="<?= $base_url ?>js/global.js" defer></script>

</head>

<body>

<header class="site-header">

    <div class="header-container">

        <!-- LOGO -->
        <?php

        $logoTienda = "";

        $stmtLogo = $conexion->prepare(
            "SELECT logo
            FROM configuracion_tienda
            WHERE id_configuracion = ?
            LIMIT 1"
        );

        if ($stmtLogo) {

            $idConfiguracion = 1;

            $stmtLogo->bind_param("i", $idConfiguracion);

            $stmtLogo->execute();

            $resultadoLogo = $stmtLogo->get_result();

            if ($filaLogo = $resultadoLogo->fetch_assoc()) {

                if (!empty($filaLogo["logo"])) {
                    $logoTienda = $base_url . "../" . $filaLogo["logo"];
                }
            }

            $stmtLogo->close();
        }

        ?>

        <a href="<?= $base_url ?>index.php" class="brand">

            <?php if ($logoTienda !== ""): ?>

                <img
                    src="<?= htmlspecialchars($logoTienda, ENT_QUOTES, 'UTF-8') ?>"
                    alt="Logo AGRANDA"
                    class="brand-logo"
                >

            <?php endif; ?>

            <span class="brand-texto">

                <span class="brand-name">AGRANDA</span>

                <span class="brand-subtitle">
                    Repuestos agrícolas de confianza
                </span>

            </span>

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

            <!-- BUSCADOR -->
            <form
                action="<?= $base_url ?>productos/"
                method="GET"
                class="header-search"
                role="search"
            >

                <input
                    type="search"
                    name="busqueda"
                    class="header-search-input"
                    placeholder="Buscar productos..."
                    maxlength="100"
                    autocomplete="off"
                    aria-label="Buscar productos"
                >

                <button
                    type="submit"
                    class="header-action header-search-button"
                    aria-label="Buscar productos"
                    title="Buscar productos"
                >

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="16.5" y1="16.5" x2="21" y2="21"></line>
                    </svg>

                </button>

            </form>

            <!-- Cuenta -->
            
            <?php if ($clienteLogueado): ?>

                <a href="<?= $base_url ?>cuenta/"
                class="header-action account-action"
                aria-label="Mi cuenta"
                title="Mi cuenta">

            <?php else: ?>

                <a href="<?= $base_url ?>cuenta/login.php"
                class="header-action account-action"
                id="abrirModalCuenta"
                aria-label="Iniciar sesión"
                title="Iniciar sesión">

            <?php endif; ?>

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="8" r="4"></circle>
                        <path d="M4 21c0-4.2 3.6-7 8-7s8 2.8 8 7"></path>
                    </svg>

                    <span class="action-text">

                        <?php if ($clienteLogueado): ?>

                            <strong>
                                Hola, <?= $nombreCliente ?>
                            </strong>

                            <small>
                                Ver mi cuenta
                            </small>

                        <?php else: ?>

                            <strong>
                                Mi cuenta
                            </strong>

                            <small>
                                Iniciar sesión
                            </small>

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

                    <span
                        class="cart-count"
                        id="contadorCarrito"
                    >
                        <?= $totalCarrito ?>
                    </span>

                </span>

                <span class="action-text">
                    <strong>Mi carrito</strong>
                    <small id="textoContadorCarrito">
                        <?= $totalCarrito ?>
                        producto<?= $totalCarrito === 1 ? "" : "s" ?>
                    </small>
                </span>

            </a>

        </div>

    </div>

</header>

<?php if (!$clienteLogueado): ?>

<div
    class="modal-cuenta"
    id="modalCuenta"
    aria-hidden="true"
>

    <div
        class="modal-cuenta-overlay"
        data-cerrar-modal-cuenta
    ></div>

    <div
        class="modal-cuenta-contenedor"
        role="dialog"
        aria-modal="true"
        aria-labelledby="tituloModalCuenta"
    >

        <button
            type="button"
            class="modal-cuenta-cerrar"
            id="cerrarModalCuenta"
            aria-label="Cerrar"
            title="Cerrar"
        >
            ×
        </button>

        <div class="modal-cuenta-contenido">

            <!-- =====================================
                 LOGIN
            ====================================== -->
            <section
                class="cuenta-panel cuenta-panel-activo"
                id="panelLogin"
            >

                <div class="cuenta-encabezado">

                    <span class="cuenta-etiqueta">
                        AGRANDA
                    </span>

                    <h2 id="tituloModalCuenta">
                        Bienvenido
                    </h2>

                    <p>
                        Inicia sesión para continuar.
                    </p>

                </div>

                <a
                    href="<?= $base_url ?>cuenta/google_login.php"
                    class="cuenta-google"
                >
                    <span class="cuenta-google-icono">
                        G
                    </span>

                    Continuar con Google
                </a>

                <div class="cuenta-separador">
                    <span>o</span>
                </div>

                <form
                    method="POST"
                    action="<?= $base_url ?>cuenta/login.php"
                    class="cuenta-formulario"
                    id="formLoginModal"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION["csrf_login"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="ajax"
                        value="1"
                    >

                    <div class="cuenta-campo">

                        <label for="modalCorreoLogin">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="modalCorreoLogin"
                            name="correo"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >

                    </div>

                    <div class="cuenta-campo">

                        <label for="modalClaveLogin">
                            Contraseña
                        </label>

                        <div class="cuenta-password">

                            <input
                                type="password"
                                id="modalClaveLogin"
                                name="clave"
                                maxlength="72"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="cuenta-ver-clave"
                                data-password-target="modalClaveLogin"
                                aria-label="Mostrar contraseña"
                            >
                                👁
                            </button>

                        </div>

                    </div>

                    <div
                        class="cuenta-mensaje cuenta-mensaje-error"
                        id="mensajeLoginModal"
                        role="alert"
                        hidden
                    ></div>

                    <button
                        type="submit"
                        class="cuenta-submit"
                    >
                        Iniciar sesión
                    </button>

                </form>

                <p class="cuenta-cambio">

                    ¿No tienes cuenta?

                    <button
                        type="button"
                        data-mostrar-registro
                    >
                        Regístrate
                    </button>

                </p>

            </section>


            <!-- =====================================
                 REGISTRO
            ====================================== -->
            <section
                class="cuenta-panel"
                id="panelRegistro"
            >

                <div class="cuenta-encabezado">

                    <span class="cuenta-etiqueta">
                        AGRANDA
                    </span>

                    <h2>
                        Crear cuenta
                    </h2>

                    <p>
                        Regístrate para guardar favoritos
                        y realizar compras.
                    </p>

                </div>

                <a
                    href="<?= $base_url ?>cuenta/google_login.php"
                    class="cuenta-google"
                >
                    <span class="cuenta-google-icono">
                        G
                    </span>

                    Continuar con Google
                </a>

                <div class="cuenta-separador">
                    <span>o</span>
                </div>

                <form
                    method="POST"
                    action="<?= $base_url ?>cuenta/registro.php"
                    class="cuenta-formulario"
                    id="formRegistroModal"
                >               

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION["csrf_registro"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="ajax"
                        value="1"
                    >

                    <div class="cuenta-doble">

                        <div class="cuenta-campo">

                            <label for="modalNombre">
                                Nombre
                            </label>

                            <input
                                type="text"
                                id="modalNombre"
                                name="nombre"
                                maxlength="100"
                                required
                            >

                        </div>

                        <div class="cuenta-campo">

                            <label for="modalApellido">
                                Apellido
                            </label>

                            <input
                                type="text"
                                id="modalApellido"
                                name="apellido"
                                maxlength="100"
                                required
                            >

                        </div>

                    </div>

                    <div class="cuenta-campo">

                        <label for="modalCorreoRegistro">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="modalCorreoRegistro"
                            name="correo"
                            maxlength="150"
                            required
                        >

                    </div>

                    <div class="cuenta-campo">

                        <label for="modalTelefono">
                            Teléfono
                        </label>

                        <input
                            type="tel"
                            id="modalTelefono"
                            name="telefono"
                            maxlength="20"
                        >

                    </div>

                    <div class="cuenta-campo">

                        <label for="modalClaveRegistro">
                            Contraseña
                        </label>

                        <div class="cuenta-password">

                            <input
                                type="password"
                                id="modalClaveRegistro"
                                name="clave"
                                maxlength="72"
                                required
                            >

                            <button
                                type="button"
                                class="cuenta-ver-clave"
                                data-password-target="modalClaveRegistro"
                                aria-label="Mostrar contraseña"
                            >
                                👁
                            </button>

                        </div>

                    </div>

                    <div class="cuenta-campo">

                        <label for="modalConfirmarClave">
                            Confirmar contraseña
                        </label>

                        <div class="cuenta-password">

                            <input
                                type="password"
                                id="modalConfirmarClave"
                                name="confirmar_clave"
                                maxlength="72"
                                required
                            >

                            <button
                                type="button"
                                class="cuenta-ver-clave"
                                data-password-target="modalConfirmarClave"
                                aria-label="Mostrar contraseña"
                            >
                                👁
                            </button>

                        </div>

                    </div>

                    <div
                        class="cuenta-mensaje cuenta-mensaje-error"
                        id="mensajeRegistroModal"
                        role="alert"
                        hidden
                    ></div>

                    <button
                        type="submit"
                        class="cuenta-submit"
                    >
                        Crear cuenta
                    </button>

                </form>

                <p class="cuenta-cambio">

                    ¿Ya tienes cuenta?

                    <button
                        type="button"
                        data-mostrar-login
                    >
                        Inicia sesión
                    </button>

                </p>

            </section>

        </div>

    </div>

</div>

<?php endif; ?>