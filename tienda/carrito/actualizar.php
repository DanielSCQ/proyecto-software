<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once("../../config/conexion.php");


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ./");
    exit;
}


// =================================
// CSRF
// =================================

$csrf =
    $_POST["csrf"] ?? "";

$csrfSesion =
    $_SESSION["csrf_carrito"] ?? "";


if (
    !is_string($csrf) ||
    !is_string($csrfSesion) ||
    $csrf === "" ||
    $csrfSesion === "" ||
    !hash_equals($csrfSesion, $csrf)
) {

    header("Location: ./");
    exit;
}


// =================================
// VALIDAR PRODUCTO
// =================================

$productoId =
    filter_input(
        INPUT_POST,
        "producto",
        FILTER_VALIDATE_INT
    );

$accion =
    $_POST["accion"] ?? "";


if (
    $productoId === false ||
    $productoId === null ||
    $productoId < 1 ||
    !in_array(
        $accion,
        ["sumar", "restar"],
        true
    )
) {

    header("Location: ./");
    exit;
}


// =================================
// COMPROBAR QUE ESTÉ EN CARRITO
// =================================

if (
    !isset($_SESSION["carrito"][$productoId]) ||
    !is_array($_SESSION["carrito"][$productoId])
) {

    header("Location: ./");
    exit;
}


$cantidadActual =
    (int) (
        $_SESSION["carrito"][$productoId]["cantidad"]
        ?? 0
    );


if ($cantidadActual < 1) {

    unset(
        $_SESSION["carrito"][$productoId]
    );

    header("Location: ./");
    exit;
}


// =================================
// RESTAR
// =================================

if ($accion === "restar") {

    if ($cantidadActual > 1) {

        $_SESSION["carrito"][$productoId]["cantidad"] =
            $cantidadActual - 1;
    }

    header("Location: ./");
    exit;
}


// =================================
// SUMAR: CONSULTAR STOCK REAL
// =================================

$stmt =
    $conexion->prepare("
        SELECT
            i.stock_actual
        FROM productos p

        INNER JOIN categorias c
            ON c.id_categoria = p.id_categoria

        INNER JOIN inventario i
            ON i.id_producto = p.id_producto

        WHERE p.id_producto = ?
          AND p.estado = 1
          AND c.estado = 1
          AND i.stock_actual > 0

        LIMIT 1
    ");


if (!$stmt) {

    header("Location: ./");
    exit;
}


$stmt->bind_param(
    "i",
    $productoId
);

$stmt->execute();

$resultado =
    $stmt->get_result();

$producto =
    $resultado->fetch_assoc();

$stmt->close();


if (!$producto) {

    header("Location: ./");
    exit;
}


$stock =
    (int) $producto["stock_actual"];


if ($cantidadActual < $stock) {

    $_SESSION["carrito"][$productoId]["cantidad"] =
        $cantidadActual + 1;
}


header("Location: ./");
exit;