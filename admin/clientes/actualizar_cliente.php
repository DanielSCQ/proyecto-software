<?php

session_start();


// =================================
// PROTEGER PANEL
// =================================

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");

    exit();
}


// =================================
// SOLO ACEPTAR POST
// =================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: clientes.php");

    exit();
}


// =================================
// CONEXIÓN
// =================================

require_once("../../config/conexion.php");


// =================================
// RECIBIR DATOS
// =================================

$id_usuario = intval($_POST["id_usuario"] ?? 0);

$nombre = trim($_POST["nombre"] ?? "");

$apellido = trim($_POST["apellido"] ?? "");

$correo = trim($_POST["correo"] ?? "");

$telefono = trim($_POST["telefono"] ?? "");

$estado = isset($_POST["estado"])
    ? intval($_POST["estado"])
    : -1;


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
// VALIDAR LONGITUDES
// =================================

if (
    mb_strlen($nombre, "UTF-8") > 100 ||
    mb_strlen($apellido, "UTF-8") > 100 ||
    mb_strlen($correo, "UTF-8") > 150 ||
    mb_strlen($telefono, "UTF-8") > 20
) {

    header(
        "Location: editar_cliente.php?id="
        . $id_usuario
        . "&error=longitud"
    );

    exit();
}


// =================================
// VALIDAR ESTADO
// =================================

if (
    $estado !== 0 &&
    $estado !== 1
) {

    header(
        "Location: editar_cliente.php?id="
        . $id_usuario
        . "&error=estado"
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
// VALIDAR TELÉFONO
// =================================

if (
    $telefono !== "" &&
    !preg_match('/^[0-9+\-\s()]{7,20}$/', $telefono)
) {

    header(
        "Location: editar_cliente.php?id="
        . $id_usuario
        . "&error=telefono"
    );

    exit();
}


// =================================
// CONSULTAR CLIENTE ACTUAL
// =================================

$sqlCliente = "SELECT
                    id_usuario,
                    nombre,
                    apellido,
                    correo,
                    telefono,
                    estado

                FROM usuarios

                WHERE id_usuario = ?
                AND rol = 'cliente'

                LIMIT 1";


$stmtCliente = $conexion->prepare($sqlCliente);


if (!$stmtCliente) {

    header("Location: clientes.php");

    exit();
}


$stmtCliente->bind_param(
    "i",
    $id_usuario
);


$stmtCliente->execute();


$resultadoCliente =
    $stmtCliente->get_result();


$clienteActual =
    $resultadoCliente->fetch_assoc();


$stmtCliente->close();


// =================================
// VERIFICAR CLIENTE
// =================================

if (!$clienteActual) {

    header("Location: clientes.php");

    exit();
}


// =================================
// VERIFICAR CORREO DUPLICADO
// =================================

$sqlCorreo = "SELECT id_usuario

              FROM usuarios

              WHERE correo = ?
              AND id_usuario != ?

              LIMIT 1";


$stmtCorreo =
    $conexion->prepare($sqlCorreo);


if (!$stmtCorreo) {

    header(
        "Location: editar_cliente.php?id="
        . $id_usuario
        . "&error=consulta"
    );

    exit();
}


$stmtCorreo->bind_param(
    "si",
    $correo,
    $id_usuario
);


$stmtCorreo->execute();


$resultadoCorreo =
    $stmtCorreo->get_result();


$correoExiste =
    $resultadoCorreo->num_rows > 0;


$stmtCorreo->close();


if ($correoExiste) {

    header(
        "Location: editar_cliente.php?id="
        . $id_usuario
        . "&error=correo_existe"
    );

    exit();
}


// =================================
// DETECTAR CAMBIOS
// =================================

$cambios = [];


if ($clienteActual["nombre"] !== $nombre) {

    $cambios[] =
        "Nombre: "
        . $clienteActual["nombre"]
        . " → "
        . $nombre;
}


if ($clienteActual["apellido"] !== $apellido) {

    $cambios[] =
        "Apellido: "
        . $clienteActual["apellido"]
        . " → "
        . $apellido;
}


if ($clienteActual["correo"] !== $correo) {

    $cambios[] =
        "Correo electrónico actualizado";
}


$telefonoAnterior =
    $clienteActual["telefono"] ?? "";


if ($telefonoAnterior !== $telefono) {

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


if (
    (int)$clienteActual["estado"]
    !==
    $estado
) {

    $estadoAnterior =
        (int)$clienteActual["estado"] === 1
            ? "Activo"
            : "Inactivo";


    $estadoNuevo =
        $estado === 1
            ? "Activo"
            : "Inactivo";


    $cambios[] =
        "Estado: "
        . $estadoAnterior
        . " → "
        . $estadoNuevo;
}


// =================================
// SI NO HUBO CAMBIOS
// =================================

if (empty($cambios)) {

    header(
        "Location: ver_clientes.php?id="
        . $id_usuario
        . "&sin_cambios=1"
    );

    exit();
}


// =================================
// INICIAR TRANSACCIÓN
// =================================

$conexion->begin_transaction();


try {


    // =================================
    // ACTUALIZAR CLIENTE
    // =================================

    $sqlActualizar = "UPDATE usuarios

                      SET
                          nombre = ?,
                          apellido = ?,
                          correo = ?,
                          telefono = ?,
                          estado = ?

                      WHERE id_usuario = ?
                      AND rol = 'cliente'";


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
        "ssssii",
        $nombre,
        $apellido,
        $correo,
        $telefono,
        $estado,
        $id_usuario
    );


    if (!$stmtActualizar->execute()) {

        throw new Exception(
            "No fue posible actualizar el cliente."
        );
    }


    if ($stmtActualizar->affected_rows < 1) {

        throw new Exception(
            "No se realizó la actualización."
        );
    }


    $stmtActualizar->close();


    // =================================
    // REGISTRAR AUDITORÍA
    // =================================

    $idAdministrador =
        (int)$_SESSION["id_usuario"];


    $accion =
        "ACTUALIZAR_CLIENTE";


    $tablaAfectada =
        "usuarios";


    $descripcion =
        "Administrador actualizó al cliente #"
        . $id_usuario
        . ". "
        . implode(
            " | ",
            $cambios
        );


    $ipUsuario =
        $_SERVER["REMOTE_ADDR"]
        ?? null;


    $sqlAuditoria = "INSERT INTO auditoria
                        (
                            id_usuario,
                            accion,
                            tabla_afectada,
                            id_registro,
                            descripcion,
                            ip_usuario
                        )

                     VALUES (?, ?, ?, ?, ?, ?)";


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
        $idAdministrador,
        $accion,
        $tablaAfectada,
        $id_usuario,
        $descripcion,
        $ipUsuario
    );


    if (!$stmtAuditoria->execute()) {

        throw new Exception(
            "No fue posible registrar la auditoría."
        );
    }


    $stmtAuditoria->close();


    // =================================
    // CONFIRMAR CAMBIOS
    // =================================

    $conexion->commit();


    header(
        "Location: ver_clientes.php?id="
        . $id_usuario
        . "&actualizado=1"
    );

    exit();


} catch (Throwable $e) {


    // =================================
    // DESHACER CAMBIOS
    // =================================

    $conexion->rollback();


    header(
        "Location: editar_cliente.php?id="
        . $id_usuario
        . "&error=actualizacion"
    );

    exit();
}
?>