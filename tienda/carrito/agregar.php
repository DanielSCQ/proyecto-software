<?php

// =================================
// SESIÓN
// =================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


// =================================
// CONEXIÓN
// =================================

require_once("../../config/conexion.php");


// =================================
// RESPUESTA JSON
// =================================

header("Content-Type: application/json; charset=UTF-8");


// =================================
// SOLO POST
// =================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método de solicitud no permitido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =================================
// CSRF
// =================================

$csrfRecibido =
    $_POST["csrf"] ?? "";

$csrfSesion =
    $_SESSION["csrf_carrito"] ?? "";


if (
    !is_string($csrfRecibido) ||
    !is_string($csrfSesion) ||
    $csrfRecibido === "" ||
    $csrfSesion === "" ||
    !hash_equals($csrfSesion, $csrfRecibido)
) {

    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "La solicitud no es válida. Recarga la página e inténtalo nuevamente."
    ], JSON_UNESCAPED_UNICODE);

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


if (
    $productoId === false ||
    $productoId === null ||
    $productoId < 1
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "El producto seleccionado no es válido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =================================
// CONSULTAR PRODUCTO Y STOCK REAL
// =================================

$sql = "
    SELECT
        p.id_producto,
        p.nombre,
        p.precio,
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
";


$stmt =
    $conexion->prepare($sql);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No fue posible validar el producto."
    ], JSON_UNESCAPED_UNICODE);

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


// =================================
// PRODUCTO NO DISPONIBLE
// =================================

if (!$producto) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "El producto ya no se encuentra disponible."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =================================
// CREAR CARRITO DE SESIÓN
// =================================

if (
    !isset($_SESSION["carrito"]) ||
    !is_array($_SESSION["carrito"])
) {

    $_SESSION["carrito"] = [];
}


// =================================
// CANTIDAD ACTUAL
// =================================

$cantidadActual = 0;

if (
    isset($_SESSION["carrito"][$productoId]) &&
    is_array($_SESSION["carrito"][$productoId])
) {

    $cantidadActual =
        (int) (
            $_SESSION["carrito"][$productoId]["cantidad"]
            ?? 0
        );
}


// =================================
// NUEVA CANTIDAD
// =================================

$nuevaCantidad =
    $cantidadActual + 1;

$stockActual =
    (int) $producto["stock_actual"];


// =================================
// VALIDAR STOCK
// =================================

if ($nuevaCantidad > $stockActual) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "No puedes agregar más unidades. El stock disponible es de "
            . $stockActual . "."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =================================
// GUARDAR EN SESIÓN
// =================================
//
// Solo guardamos información necesaria.
// Precio, nombre y stock se consultarán
// nuevamente desde la base de datos.
//

$_SESSION["carrito"][$productoId] = [
    "cantidad" => $nuevaCantidad
];


// =================================
// CALCULAR TOTAL DE UNIDADES
// =================================

$totalUnidades = 0;

foreach ($_SESSION["carrito"] as $item) {

    $cantidad =
        (int) ($item["cantidad"] ?? 0);

    if ($cantidad > 0) {
        $totalUnidades += $cantidad;
    }
}


// =================================
// RESPUESTA
// =================================

echo json_encode([
    "success" => true,
    "message" =>
        $cantidadActual > 0
            ? "Se agregó otra unidad al carrito."
            : "Producto agregado al carrito.",
    "cantidad_producto" =>
        $nuevaCantidad,
    "cantidad_carrito" =>
        $totalUnidades
], JSON_UNESCAPED_UNICODE);

exit;