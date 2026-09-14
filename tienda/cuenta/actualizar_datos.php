<?php

// ==========================================
// SESIÓN
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==========================================
// SOLO ACEPTAR POST
// ==========================================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");

    exit();
}


// ==========================================
// PROTEGER CUENTA
// ==========================================
if (
    !isset($_SESSION["id_usuario"]) ||
    !is_numeric($_SESSION["id_usuario"]) ||
    ($_SESSION["rol"] ?? "") !== "cliente"
) {

    header("Location: login.php");

    exit();
}


// ==========================================
// CONEXIÓN
// ==========================================
require_once __DIR__ . "/../../config/conexion.php";


// ==========================================
// ID DEL CLIENTE
// ==========================================
$idUsuario =
    (int) $_SESSION["id_usuario"];


// ==========================================
// VALIDAR CSRF
// ==========================================
$csrf =
    $_POST["csrf_token"]
    ?? "";


if (
    empty($_SESSION["csrf_editar_datos"]) ||
    !is_string($csrf) ||
    !hash_equals(
        $_SESSION["csrf_editar_datos"],
        $csrf
    )
) {

    header(
        "Location: index.php?error=seguridad"
    );

    exit();
}


// ==========================================
// RECIBIR ÚNICAMENTE DATOS PERMITIDOS
// ==========================================
$nombre =
    trim($_POST["nombre"] ?? "");

$apellido =
    trim($_POST["apellido"] ?? "");

$telefono =
    trim($_POST["telefono"] ?? "");


// ==========================================
// CAMPOS OBLIGATORIOS
// ==========================================
if (
    $nombre === "" ||
    $apellido === ""
) {

    header(
        "Location: index.php?error=campos"
    );

    exit();
}


// ==========================================
// VALIDAR LONGITUDES
// ==========================================
if (
    mb_strlen($nombre, "UTF-8") > 100 ||
    mb_strlen($apellido, "UTF-8") > 100 ||
    mb_strlen($telefono, "UTF-8") > 20
) {

    header(
        "Location: index.php?error=longitud"
    );

    exit();
}


// ==========================================
// VALIDAR CARACTERES DE CONTROL
// ==========================================
if (
    preg_match('/[\x00-\x1F\x7F]/u', $nombre) ||
    preg_match('/[\x00-\x1F\x7F]/u', $apellido)
) {

    header(
        "Location: index.php?error=datos_invalidos"
    );

    exit();
}


// ==========================================
// VALIDAR TELÉFONO
// ==========================================
if (
    $telefono !== "" &&
    !preg_match(
        '/^[0-9+\-\s()]{7,20}$/',
        $telefono
    )
) {

    header(
        "Location: index.php?error=telefono"
    );

    exit();
}


// ==========================================
// CONSULTAR CLIENTE ACTUAL
// ==========================================
$sqlCliente = "
    SELECT
        id_usuario,
        nombre,
        apellido,
        telefono,
        correo,
        estado
    FROM usuarios
    WHERE id_usuario = ?
      AND rol = 'cliente'
    LIMIT 1
";


$stmtCliente =
    $conexion->prepare($sqlCliente);


if (!$stmtCliente) {

    header(
        "Location: index.php?error=consulta"
    );

    exit();
}


$stmtCliente->bind_param(
    "i",
    $idUsuario
);


$stmtCliente->execute();


$resultadoCliente =
    $stmtCliente->get_result();


$clienteActual =
    $resultadoCliente->fetch_assoc();


$stmtCliente->close();


// ==========================================
// VERIFICAR CLIENTE
// ==========================================
if (
    !$clienteActual ||
    (int) $clienteActual["estado"] !== 1
) {

    $_SESSION = [];

    session_destroy();

    header(
        "Location: login.php"
    );

    exit();
}


// ==========================================
// DETECTAR CAMBIOS
// ==========================================
$cambios = [];


if (
    $clienteActual["nombre"]
    !==
    $nombre
) {

    $cambios[] =
        "Nombre: "
        . $clienteActual["nombre"]
        . " → "
        . $nombre;
}


if (
    $clienteActual["apellido"]
    !==
    $apellido
) {

    $cambios[] =
        "Apellido: "
        . $clienteActual["apellido"]
        . " → "
        . $apellido;
}


$telefonoAnterior =
    $clienteActual["telefono"]
    ?? "";


if (
    $telefonoAnterior
    !==
    $telefono
) {

    $cambios[] =
        "Teléfono: "
        . (
            $telefonoAnterior !== ""
                ? $telefonoAnterior
                : "No registrado"
        )
        . " → "
        . (
            $telefono !== ""
                ? $telefono
                : "No registrado"
        );
}


// ==========================================
// SIN CAMBIOS
// ==========================================
if (empty($cambios)) {

    header(
        "Location: index.php?sin_cambios=1"
    );

    exit();
}


// ==========================================
// INICIAR TRANSACCIÓN
// ==========================================
$conexion->begin_transaction();


try {


    // ======================================
    // ACTUALIZAR SOLO DATOS PERMITIDOS
    // ======================================
    $sqlActualizar = "
        UPDATE usuarios
        SET
            nombre = ?,
            apellido = ?,
            telefono = ?
        WHERE id_usuario = ?
          AND rol = 'cliente'
          AND estado = 1
    ";


    $stmtActualizar =
        $conexion->prepare(
            $sqlActualizar
        );


    if (!$stmtActualizar) {

        throw new Exception(
            "No fue posible preparar la actualización."
        );
    }


    $stmtActualizar->bind_param(
        "sssi",
        $nombre,
        $apellido,
        $telefono,
        $idUsuario
    );


    if (!$stmtActualizar->execute()) {

        throw new Exception(
            "No fue posible actualizar los datos."
        );
    }


    if (
        $stmtActualizar->affected_rows < 1
    ) {

        throw new Exception(
            "No se realizó la actualización."
        );
    }


    $stmtActualizar->close();


    // ======================================
    // AUDITORÍA
    // ======================================
    $accion =
        "ACTUALIZAR_DATOS_CLIENTE";


    $tablaAfectada =
        "usuarios";


    $descripcion =
        "Cliente #"
        . $idUsuario
        . " actualizó sus datos personales. "
        . implode(
            " | ",
            $cambios
        );


    $ipUsuario =
        $_SERVER["REMOTE_ADDR"]
        ?? null;


    $sqlAuditoria = "
        INSERT INTO auditoria
        (
            id_usuario,
            accion,
            tabla_afectada,
            id_registro,
            descripcion,
            ip_usuario
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ";


    $stmtAuditoria =
        $conexion->prepare(
            $sqlAuditoria
        );


    if (!$stmtAuditoria) {

        throw new Exception(
            "No fue posible preparar la auditoría."
        );
    }


    $stmtAuditoria->bind_param(
        "ississ",
        $idUsuario,
        $accion,
        $tablaAfectada,
        $idUsuario,
        $descripcion,
        $ipUsuario
    );


    if (!$stmtAuditoria->execute()) {

        throw new Exception(
            "No fue posible registrar la auditoría."
        );
    }


    $stmtAuditoria->close();


    // ======================================
    // CONFIRMAR
    // ======================================
    $conexion->commit();


    // ======================================
    // SINCRONIZAR SESIÓN
    // ======================================
    $_SESSION["nombre"] =
        $nombre;

    $_SESSION["apellido"] =
        $apellido;


    // ======================================
    // RENOVAR TOKEN CSRF
    // ======================================
    $_SESSION["csrf_editar_datos"] =
        bin2hex(
            random_bytes(32)
        );


    header(
        "Location: index.php?actualizado=1"
    );

    exit();


} catch (Throwable $e) {


    // ======================================
    // DESHACER TODO
    // ======================================
    $conexion->rollback();


    header(
        "Location: index.php?error=actualizacion"
    );

    exit();
}