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

require_once("../../config/conexion.php");


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


// =========================================
// SOLO POST
// =========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


// =========================================
// SOLO CLIENTES
// =========================================

if (
    !isset($_SESSION["id_usuario"]) ||
    ($_SESSION["rol"] ?? "") !== "cliente"
) {

    header(
        "Location: " .
        $base_url .
        "index.php"
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
    $_SESSION["csrf_checkout"] ?? "";


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

    $_SESSION["checkout_error"] =
        "La solicitud no es válida. Recarga la página e inténtalo nuevamente.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


// =========================================
// RECIBIR DATOS
// =========================================

$nombre =
    trim(
        $_POST["nombre"] ?? ""
    );

$receptor =
    trim(
        $_POST["receptor"] ?? ""
    );

$telefono =
    trim(
        $_POST["telefono"] ?? ""
    );

$direccion =
    trim(
        $_POST["direccion"] ?? ""
    );

$barrio =
    trim(
        $_POST["barrio"] ?? ""
    );

$municipio =
    trim(
        $_POST["municipio"] ?? ""
    );

$departamento =
    trim(
        $_POST["departamento"] ?? ""
    );

$referencia =
    trim(
        $_POST["referencia"] ?? ""
    );

$principal =
    isset($_POST["principal"]) &&
    $_POST["principal"] === "1"
        ? 1
        : 0;


// =========================================
// GUARDAR DATOS TEMPORALES
// =========================================

$_SESSION["checkout_direccion_form"] = [
    "nombre" => $nombre,
    "receptor" => $receptor,
    "telefono" => $telefono,
    "direccion" => $direccion,
    "barrio" => $barrio,
    "municipio" => $municipio,
    "departamento" => $departamento,
    "referencia" => $referencia,
    "principal" => $principal
];


// =========================================
// VALIDACIONES
// =========================================

$errores = [];


// -----------------------------------------
// NOMBRE DIRECCIÓN
// -----------------------------------------

if ($nombre === "") {

    $errores[] =
        "Escribe un nombre para la dirección.";

} elseif (mb_strlen($nombre) > 100) {

    $errores[] =
        "El nombre de la dirección no puede superar los 100 caracteres.";
}


// -----------------------------------------
// RECEPTOR
// -----------------------------------------

if ($receptor === "") {

    $errores[] =
        "Escribe el nombre de quien recibirá el pedido.";

} elseif (mb_strlen($receptor) > 200) {

    $errores[] =
        "El nombre del receptor no puede superar los 200 caracteres.";
}


// -----------------------------------------
// TELÉFONO
// -----------------------------------------

if ($telefono === "") {

    $errores[] =
        "Escribe un número de teléfono.";

} elseif (mb_strlen($telefono) > 20) {

    $errores[] =
        "El teléfono no puede superar los 20 caracteres.";

} elseif (
    !preg_match(
        '/^[0-9+\s()-]{7,20}$/',
        $telefono
    )
) {

    $errores[] =
        "El teléfono contiene caracteres no válidos.";
}


// -----------------------------------------
// DIRECCIÓN
// -----------------------------------------

if ($direccion === "") {

    $errores[] =
        "Escribe la dirección de entrega.";

} elseif (mb_strlen($direccion) > 200) {

    $errores[] =
        "La dirección no puede superar los 200 caracteres.";
}


// -----------------------------------------
// BARRIO
// -----------------------------------------

if (
    $barrio !== "" &&
    mb_strlen($barrio) > 100
) {

    $errores[] =
        "El barrio no puede superar los 100 caracteres.";
}


// -----------------------------------------
// MUNICIPIO
// -----------------------------------------

if ($municipio === "") {

    $errores[] =
        "Escribe el municipio.";

} elseif (mb_strlen($municipio) > 100) {

    $errores[] =
        "El municipio no puede superar los 100 caracteres.";
}


// -----------------------------------------
// DEPARTAMENTO
// -----------------------------------------

if ($departamento === "") {

    $errores[] =
        "Escribe el departamento.";

} elseif (mb_strlen($departamento) > 100) {

    $errores[] =
        "El departamento no puede superar los 100 caracteres.";
}


// -----------------------------------------
// REFERENCIA
// -----------------------------------------

if (
    $referencia !== "" &&
    mb_strlen($referencia) > 500
) {

    $errores[] =
        "La referencia no puede superar los 500 caracteres.";
}


// =========================================
// SI HAY ERRORES
// =========================================

if (!empty($errores)) {

    $_SESSION["checkout_errores"] =
        $errores;

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


// =========================================
// VERIFICAR SI YA TIENE DIRECCIONES
// =========================================

$sqlCantidad = "
    SELECT COUNT(*) AS total
    FROM direcciones
    WHERE id_usuario = ?
      AND estado = 1
";

$stmtCantidad =
    $conexion->prepare(
        $sqlCantidad
    );


if (!$stmtCantidad) {

    $_SESSION["checkout_error"] =
        "No fue posible guardar la dirección.";

    header(
        "Location: " .
        $base_url .
        "checkout/"
    );

    exit;
}


$stmtCantidad->bind_param(
    "i",
    $idUsuario
);

$stmtCantidad->execute();

$resultadoCantidad =
    $stmtCantidad
        ->get_result()
        ->fetch_assoc();

$stmtCantidad->close();


$totalDirecciones =
    (int) (
        $resultadoCantidad["total"]
        ?? 0
    );


// =========================================
// PRIMERA DIRECCIÓN = PRINCIPAL
// =========================================

if ($totalDirecciones === 0) {

    $principal = 1;
}


// =========================================
// TRANSACCIÓN
// =========================================

$conexion->begin_transaction();


try {

    // =====================================
    // SI SERÁ PRINCIPAL,
    // QUITAR PRINCIPAL A LAS DEMÁS
    // =====================================

    if ($principal === 1) {

        $sqlQuitarPrincipal = "
            UPDATE direcciones
            SET principal = 0
            WHERE id_usuario = ?
        ";

        $stmtQuitarPrincipal =
            $conexion->prepare(
                $sqlQuitarPrincipal
            );


        if (!$stmtQuitarPrincipal) {

            throw new Exception(
                "No fue posible actualizar las direcciones."
            );
        }


        $stmtQuitarPrincipal->bind_param(
            "i",
            $idUsuario
        );

        $stmtQuitarPrincipal->execute();

        $stmtQuitarPrincipal->close();
    }


    // =====================================
    // INSERTAR DIRECCIÓN
    // =====================================

    $sqlInsertar = "
        INSERT INTO direcciones (
            id_usuario,
            nombre,
            telefono,
            receptor,
            direccion,
            barrio,
            municipio,
            departamento,
            referencia,
            principal,
            estado
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            NULLIF(?, ''),
            ?,
            ?,
            NULLIF(?, ''),
            ?,
            1
        )
    ";


    $stmtInsertar =
        $conexion->prepare(
            $sqlInsertar
        );


    if (!$stmtInsertar) {

        throw new Exception(
            "No fue posible preparar el registro de la dirección."
        );
    }


    $stmtInsertar->bind_param(
        "issssssssi",
        $idUsuario,
        $nombre,
        $telefono,
        $receptor,
        $direccion,
        $barrio,
        $municipio,
        $departamento,
        $referencia,
        $principal
    );


    if (!$stmtInsertar->execute()) {

        throw new Exception(
            "No fue posible registrar la dirección."
        );
    }


    $idDireccionNueva =
        $conexion->insert_id;


    $stmtInsertar->close();


    // =====================================
    // CONFIRMAR TRANSACCIÓN
    // =====================================

    $conexion->commit();


    // =====================================
    // LIMPIAR FORMULARIO TEMPORAL
    // =====================================

    unset(
        $_SESSION["checkout_direccion_form"],
        $_SESSION["checkout_errores"]
    );


    // Guardamos cuál acabamos de crear
    // para seleccionarla en checkout.
    $_SESSION["checkout_direccion_seleccionada"] =
        (int) $idDireccionNueva;


    $_SESSION["checkout_exito"] =
        "Dirección guardada correctamente.";


} catch (Throwable $e) {

    $conexion->rollback();


    $_SESSION["checkout_error"] =
        "No fue posible guardar la dirección en este momento.";
}


// =========================================
// VOLVER AL CHECKOUT
// =========================================

header(
    "Location: " .
    $base_url .
    "checkout/"
);

exit;