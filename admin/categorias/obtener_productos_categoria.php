<?php

session_start();

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION["id_usuario"])) {

    http_response_code(401);

    echo json_encode([
        "exito" => false,
        "mensaje" => "No tienes autorización para realizar esta acción."
    ]);

    exit();

}

require_once("../../config/conexion.php");

// Indicar que la respuesta será JSON
header("Content-Type: application/json; charset=UTF-8");


/*
=================================
    VERIFICAR ID DE CATEGORÍA
=================================
*/

if (!isset($_GET["id"]) || !ctype_digit($_GET["id"])) {

    http_response_code(400);

    echo json_encode([
        "exito" => false,
        "mensaje" => "El ID de la categoría no es válido."
    ]);

    exit();

}

$id_categoria = (int) $_GET["id"];


/*
=================================
    VERIFICAR QUE LA CATEGORÍA EXISTA
=================================
*/

$sqlCategoria = "
    SELECT id_categoria, nombre
    FROM categorias
    WHERE id_categoria = ?
";

$stmtCategoria = $conexion->prepare($sqlCategoria);

if (!$stmtCategoria) {

    http_response_code(500);

    echo json_encode([
        "exito" => false,
        "mensaje" => "No fue posible consultar la categoría."
    ]);

    exit();

}

$stmtCategoria->bind_param("i", $id_categoria);

$stmtCategoria->execute();

$resultadoCategoria = $stmtCategoria->get_result();

$categoria = $resultadoCategoria->fetch_assoc();

$stmtCategoria->close();


if (!$categoria) {

    http_response_code(404);

    echo json_encode([
        "exito" => false,
        "mensaje" => "La categoría no existe."
    ]);

    exit();

}


/*
=================================
    OBTENER PRODUCTOS
=================================
*/

$sqlProductos = "
    SELECT id_producto, nombre
    FROM productos
    WHERE id_categoria = ?
    ORDER BY nombre ASC
";

$stmtProductos = $conexion->prepare($sqlProductos);

if (!$stmtProductos) {

    http_response_code(500);

    echo json_encode([
        "exito" => false,
        "mensaje" => "No fue posible consultar los productos."
    ]);

    exit();

}

$stmtProductos->bind_param("i", $id_categoria);

$stmtProductos->execute();

$resultadoProductos = $stmtProductos->get_result();

$productos = [];


while ($producto = $resultadoProductos->fetch_assoc()) {

    $productos[] = [
        "id_producto" => (int) $producto["id_producto"],
        "nombre" => $producto["nombre"]
    ];

}

$stmtProductos->close();


/*
=================================
    DEVOLVER RESPUESTA
=================================
*/

echo json_encode([
    "exito" => true,
    "categoria" => [
        "id_categoria" => $id_categoria,
        "nombre" => $categoria["nombre"]
    ],
    "productos" => $productos
], JSON_UNESCAPED_UNICODE);

exit();