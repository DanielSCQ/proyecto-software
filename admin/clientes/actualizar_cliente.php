<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// RECIBIR DATOS
// =================================

$id_usuario = intval($_POST["id_usuario"] ?? 0);

$nombre = trim($_POST["nombre"] ?? "");

$apellido = trim($_POST["apellido"] ?? "");

$correo = trim($_POST["correo"] ?? "");

$telefono = trim($_POST["telefono"] ?? "");

$estado = isset($_POST["estado"]) ? intval($_POST["estado"]) : 0;


// =================================
// VALIDAR ID
// =================================

if ($id_usuario <= 0) {

    header("Location: clientes.php");

    exit();
}


// =================================
// VALIDAR CAMPOS OBLIGATORIOS
// =================================

if (
    $nombre === "" ||
    $apellido === "" ||
    $correo === ""
) {

    header(
        "Location: editar_cliente.php?id="
        . $id_usuario
        . "&error=campos"
    );

    exit();
}


// =================================
// VALIDAR CORREO
// =================================

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

    header(
        "Location: editar_cliente.php?id="
        . $id_usuario
        . "&error=correo"
    );

    exit();
}


// =================================
// VERIFICAR CORREO DUPLICADO
// =================================

$sql_correo = "SELECT id_usuario
               FROM usuarios
               WHERE correo = ?
               AND id_usuario != ?";

$stmt_correo = $conexion->prepare($sql_correo);

$stmt_correo->bind_param(
    "si",
    $correo,
    $id_usuario
);

$stmt_correo->execute();

$resultado_correo = $stmt_correo->get_result();


if ($resultado_correo->num_rows > 0) {

    header(
        "Location: editar_cliente.php?id="
        . $id_usuario
        . "&error=correo_existe"
    );

    exit();
}


// =================================
// ACTUALIZAR CLIENTE
// =================================

$sql = "UPDATE usuarios

        SET
            nombre = ?,
            apellido = ?,
            correo = ?,
            telefono = ?,
            estado = ?

        WHERE id_usuario = ?
        AND rol = 'cliente'";


$stmt = $conexion->prepare($sql);


if (!$stmt) {

    die(
        "Error al preparar la actualización: "
        . $conexion->error
    );

}


$stmt->bind_param(
    "ssssii",
    $nombre,
    $apellido,
    $correo,
    $telefono,
    $estado,
    $id_usuario
);


// =================================
// EJECUTAR
// =================================

if ($stmt->execute()) {

    header(
        "Location: ver_cliente.php?id="
        . $id_usuario
        . "&actualizado=1"
    );

    exit();

} else {

    die(
        "Error al actualizar el cliente: "
        . $stmt->error
    );

}

?>