<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vaciar variables de sesión
$_SESSION = [];

// Eliminar cookie de sesión si existe
if (ini_get("session.use_cookies")) {

    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $parametros["path"],
        $parametros["domain"],
        $parametros["secure"],
        $parametros["httponly"]
    );
}

// Destruir sesión
session_destroy();

// Obtener ruta base
$scriptDir = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
$posTienda = strpos($scriptDir, "/tienda");

if ($posTienda !== false) {
    $base_url = substr($scriptDir, 0, $posTienda + strlen("/tienda")) . "/";
} else {
    $base_url = "/tienda/";
}

header("Location: " . $base_url . "index.php");
exit;