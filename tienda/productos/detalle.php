<?php

// =================================
// CONEXIÓN
// =================================

require_once("../../config/conexion.php");


// =================================
// RESPUESTA JSON
// =================================

header("Content-Type: application/json; charset=UTF-8");


// =================================
// SOLO SE PERMITEN PETICIONES GET
// =================================

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método de solicitud no permitido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =================================
// OBTENER ID DEL PRODUCTO
// =================================

$productoId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


// =================================
// VALIDAR ID
// =================================

if (
    $productoId === false ||
    $productoId === null ||
    $productoId < 1
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "El producto solicitado no es válido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =================================
// OBTENER PRODUCTO
// =================================

$sqlProducto = "
    SELECT
        p.id_producto,
        p.nombre,
        p.descripcion,
        p.codigo_producto,
        p.precio,
        p.peso,
        p.id_categoria,
        c.nombre AS categoria_nombre,
        m.nombre AS marca_nombre,
        i.stock_actual,
        img.ruta_imagen

    FROM productos p

    INNER JOIN categorias c
        ON c.id_categoria = p.id_categoria

    LEFT JOIN marcas m
        ON m.id_marca = p.id_marca

    INNER JOIN inventario i
        ON i.id_producto = p.id_producto

    LEFT JOIN imagenes_producto img
        ON img.id_producto = p.id_producto
        AND img.principal = 1
        AND img.estado = 1

    WHERE p.id_producto = ?
      AND p.estado = 1
      AND c.estado = 1
      AND i.stock_actual > 0

    LIMIT 1
";


$stmtProducto = $conexion->prepare($sqlProducto);


if (!$stmtProducto) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No fue posible consultar el producto."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$stmtProducto->bind_param("i", $productoId);

$stmtProducto->execute();

$resultadoProducto = $stmtProducto->get_result();

$producto = $resultadoProducto->fetch_assoc();

$stmtProducto->close();


if (!$producto) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "El producto no está disponible."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =================================
// OBTENER CARACTERÍSTICAS
// =================================

$caracteristicas = [];

$sqlCaracteristicas = "
    SELECT
        ap.nombre,
        pa.valor

    FROM producto_atributo pa

    INNER JOIN atributos_producto ap
        ON ap.id_atributo = pa.id_atributo

    WHERE pa.id_producto = ?
      AND ap.estado = 1

    ORDER BY ap.nombre ASC
";


$stmtCaracteristicas = $conexion->prepare($sqlCaracteristicas);


if ($stmtCaracteristicas) {

    $stmtCaracteristicas->bind_param("i", $productoId);

    $stmtCaracteristicas->execute();

    $resultadoCaracteristicas =
        $stmtCaracteristicas->get_result();


    while ($caracteristica =
        $resultadoCaracteristicas->fetch_assoc()
    ) {

        $caracteristicas[] = [
            "nombre" => $caracteristica["nombre"],
            "valor" => $caracteristica["valor"]
        ];

    }

    $stmtCaracteristicas->close();
}


// =================================
// PREPARAR RESPUESTA
// =================================

$datosProducto = [

    "id_producto" =>
        (int) $producto["id_producto"],

    "nombre" =>
        $producto["nombre"],

    "descripcion" =>
        $producto["descripcion"],

    "codigo_producto" =>
        $producto["codigo_producto"],

    "precio" =>
        (float) $producto["precio"],

    "peso" =>
        $producto["peso"] !== null
            ? (float) $producto["peso"]
            : null,

    "categoria" =>
        $producto["categoria_nombre"],

    "marca" =>
        $producto["marca_nombre"],

    "stock" =>
        (int) $producto["stock_actual"],

    "imagen" =>
        $producto["ruta_imagen"],

    "caracteristicas" =>
        $caracteristicas
];


// =================================
// RESPUESTA EXITOSA
// =================================

echo json_encode([
    "success" => true,
    "producto" => $datosProducto
], JSON_UNESCAPED_UNICODE);

exit;