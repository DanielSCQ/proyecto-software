<?php

// =========================================
// SESIÓN
// =========================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


// =========================================
// CONEXIÓN
// =========================================

require_once __DIR__ . "/../../config/conexion.php";


// =========================================
// URL BASE
// =========================================

$scriptDir =
    str_replace(
        "\\",
        "/",
        dirname($_SERVER["SCRIPT_NAME"])
    );

$posTienda =
    strpos(
        $scriptDir,
        "/tienda"
    );

if ($posTienda !== false) {

    $base_url =
        substr(
            $scriptDir,
            0,
            $posTienda + strlen("/tienda")
        ) . "/";

} else {

    $base_url = "/tienda/";
}


$urlDirecciones =
    $base_url .
    "cuenta/?vista=direcciones";


// =========================================
// SOLO POST
// =========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: " .
        $urlDirecciones
    );

    exit;
}


// =========================================
// VALIDAR CLIENTE
// =========================================

if (
    !isset($_SESSION["id_usuario"]) ||
    !is_numeric($_SESSION["id_usuario"]) ||
    ($_SESSION["rol"] ?? "") !== "cliente"
) {

    header(
        "Location: " .
        $base_url .
        "cuenta/login.php"
    );

    exit;
}


$idUsuario =
    (int) $_SESSION["id_usuario"];


// =========================================
// CSRF
// =========================================

$csrfRecibido =
    $_POST["csrf"] ?? "";

$csrfSesion =
    $_SESSION["csrf_direcciones"] ?? "";


if (
    !is_string($csrfRecibido) ||
    !is_string($csrfSesion) ||
    $csrfRecibido === "" ||
    $csrfSesion === "" ||
    !hash_equals(
        $csrfSesion,
        $csrfRecibido
    )
) {

    $_SESSION["direccion_error"] =
        "La solicitud no es válida. Recarga la página e inténtalo nuevamente.";

    header(
        "Location: " .
        $urlDirecciones
    );

    exit;
}


// =========================================
// ID DIRECCIÓN
// =========================================

$idDireccion =
    filter_input(
        INPUT_POST,
        "id_direccion",
        FILTER_VALIDATE_INT
    );


if (
    $idDireccion === false ||
    $idDireccion === null ||
    $idDireccion <= 0
) {

    $_SESSION["direccion_error"] =
        "La dirección seleccionada no es válida.";

    header(
        "Location: " .
        $urlDirecciones
    );

    exit;
}


// =========================================
// VERIFICAR QUE PERTENECE AL CLIENTE
// =========================================

$sqlDireccion = "
    SELECT
        id_direccion,
        principal
    FROM direcciones
    WHERE id_direccion = ?
      AND id_usuario = ?
      AND estado = 1
    LIMIT 1
";


$stmtDireccion =
    $conexion->prepare(
        $sqlDireccion
    );


if (!$stmtDireccion) {

    $_SESSION["direccion_error"] =
        "No fue posible actualizar la dirección principal.";

    header(
        "Location: " .
        $urlDirecciones
    );

    exit;
}


$stmtDireccion->bind_param(
    "ii",
    $idDireccion,
    $idUsuario
);

$stmtDireccion->execute();

$direccionActual =
    $stmtDireccion
        ->get_result()
        ->fetch_assoc();

$stmtDireccion->close();


if (!$direccionActual) {

    $_SESSION["direccion_error"] =
        "La dirección seleccionada no existe.";

    header(
        "Location: " .
        $urlDirecciones
    );

    exit;
}


// =========================================
// SI YA ES PRINCIPAL
// =========================================

if (
    (int) $direccionActual["principal"] === 1
) {

    $_SESSION["direccion_exito"] =
        "Esta dirección ya es la principal.";

    header(
        "Location: " .
        $urlDirecciones
    );

    exit;
}


// =========================================
// TRANSACCIÓN
// =========================================

$conexion->begin_transaction();


try {

    // =====================================
    // QUITAR PRINCIPAL A LAS DEMÁS
    // =====================================

    $sqlQuitarPrincipal = "
        UPDATE direcciones
        SET principal = 0
        WHERE id_usuario = ?
          AND estado = 1
    ";


    $stmtQuitarPrincipal =
        $conexion->prepare(
            $sqlQuitarPrincipal
        );


    if (!$stmtQuitarPrincipal) {

        throw new Exception(
            "No fue posible preparar la actualización."
        );
    }


    $stmtQuitarPrincipal->bind_param(
        "i",
        $idUsuario
    );


    if (
        !$stmtQuitarPrincipal->execute()
    ) {

        throw new Exception(
            "No fue posible actualizar las direcciones."
        );
    }


    $stmtQuitarPrincipal->close();


    // =====================================
    // ASIGNAR NUEVA PRINCIPAL
    // =====================================

    $sqlNuevaPrincipal = "
        UPDATE direcciones
        SET principal = 1
        WHERE id_direccion = ?
          AND id_usuario = ?
          AND estado = 1
    ";


    $stmtNuevaPrincipal =
        $conexion->prepare(
            $sqlNuevaPrincipal
        );


    if (!$stmtNuevaPrincipal) {

        throw new Exception(
            "No fue posible preparar la dirección principal."
        );
    }


    $stmtNuevaPrincipal->bind_param(
        "ii",
        $idDireccion,
        $idUsuario
    );


    if (
        !$stmtNuevaPrincipal->execute()
    ) {

        throw new Exception(
            "No fue posible establecer la dirección principal."
        );
    }


    $stmtNuevaPrincipal->close();


    // =====================================
    // CONFIRMAR
    // =====================================

    $conexion->commit();


    $_SESSION["direccion_exito"] =
        "Dirección principal actualizada correctamente.";


} catch (Throwable $e) {

    $conexion->rollback();


    $_SESSION["direccion_error"] =
        "No fue posible cambiar la dirección principal en este momento.";
}


// =========================================
// VOLVER
// =========================================

header(
    "Location: " .
    $urlDirecciones
);

exit;