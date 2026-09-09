<?php

// Detectar si AGRANDA está ejecutándose localmente o en el hosting
$esLocal = in_array(
    $_SERVER["HTTP_HOST"] ?? "",
    ["localhost", "127.0.0.1"]
);

if ($esLocal) {
    // XAMPP
    $host = "localhost";
    $usuario = "root";
    $contrasena = "";
    $basedatos = "base de datos tienda-inventario";
} else {

    require __DIR__ . "/credenciales_servidor.php";
}
$conexion = new mysqli($host, $usuario, $contrasena, $basedatos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

?>