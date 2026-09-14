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
// ID DE LA DIRECCIÓN
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
// VERIFICAR PROPIEDAD
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
        "No fue posible eliminar la dirección.";

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


$eraPrincipal =
    (int) $direccionActual["principal"] === 1;


// =========================================
// TRANSACCIÓN
// =========================================

$conexion->begin_transaction();


try {

    // =====================================
    // DESACTIVAR DIRECCIÓN
    // =====================================

    $sqlEliminar = "
        UPDATE direcciones
        SET
            estado = 0,
            principal = 0
        WHERE id_direccion = ?
          AND id_usuario = ?
          AND estado = 1
    ";


    $stmtEliminar =
        $conexion->prepare(
            $sqlEliminar
        );


    if (!$stmtEliminar) {

        throw new Exception(
            "No fue posible preparar la eliminación."
        );
    }


    $stmtEliminar->bind_param(
        "ii",
        $idDireccion,
        $idUsuario
    );


    if (!$stmtEliminar->execute()) {

        throw new Exception(
            "No fue posible eliminar la dirección."
        );
    }


    $stmtEliminar->close();


    // =====================================
    // SI ERA PRINCIPAL,
    // ASIGNAR OTRA COMO PRINCIPAL
    // =====================================

    if ($eraPrincipal) {

        $sqlOtraDireccion = "
            SELECT id_direccion
            FROM direcciones
            WHERE id_usuario = ?
              AND estado = 1
            ORDER BY id_direccion DESC
            LIMIT 1
        ";


        $stmtOtraDireccion =
            $conexion->prepare(
                $sqlOtraDireccion
            );


        if (!$stmtOtraDireccion) {

            throw new Exception(
                "No fue posible buscar otra dirección."
            );
        }


        $stmtOtraDireccion->bind_param(
            "i",
            $idUsuario
        );

        $stmtOtraDireccion->execute();

        $otraDireccion =
            $stmtOtraDireccion
                ->get_result()
                ->fetch_assoc();

        $stmtOtraDireccion->close();


        if ($otraDireccion) {

            $idNuevaPrincipal =
                (int) $otraDireccion["id_direccion"];


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
                    "No fue posible asignar la nueva dirección principal."
                );
            }


            $stmtNuevaPrincipal->bind_param(
                "ii",
                $idNuevaPrincipal,
                $idUsuario
            );


            if (
                !$stmtNuevaPrincipal->execute()
            ) {

                throw new Exception(
                    "No fue posible asignar la nueva dirección principal."
                );
            }


            $stmtNuevaPrincipal->close();
        }
    }


    // =====================================
    // CONFIRMAR
    // =====================================

    $conexion->commit();


    $_SESSION["direccion_exito"] =
        "Dirección eliminada correctamente.";


} catch (Throwable $e) {

    $conexion->rollback();


    $_SESSION["direccion_error"] =
        "No fue posible eliminar la dirección en este momento.";
}


// =========================================
// VOLVER A DIRECCIONES
// =========================================

header(
    "Location: " .
    $urlDirecciones
);

exit;