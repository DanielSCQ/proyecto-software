<?php

require_once __DIR__ . "/config/correo.php";

$correoDestino = "AGRANDA.Tienda@gmail.com";
$nombreDestino = "Prueba AGRANDA";

$urlPrueba =
    "http://localhost/diseño%20del%20proyecto%20software/"
    . "tienda/cuenta/verificar_correo.php?token="
    . str_repeat("a", 64);

$enviado =
    enviarCorreoVerificacion(
        $correoDestino,
        $nombreDestino,
        $urlPrueba
    );

if ($enviado) {

    echo "CORREO ENVIADO CORRECTAMENTE";

} else {

    echo "NO FUE POSIBLE ENVIAR EL CORREO";
}