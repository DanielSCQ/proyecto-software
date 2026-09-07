<?php

declare(strict_types=1);

session_start();

header("Content-Type: application/json; charset=UTF-8");

require_once("../../config/conexion.php");


function responder(
    bool $success,
    string $message,
    array $extra = [],
    int $status = 200
): never {

    http_response_code($status);

    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// =========================================
// SOLO CLIENTES AUTENTICADOS
// =========================================

$idUsuario = $_SESSION["id_usuario"] ?? null;
$rol = $_SESSION["rol"] ?? null;

if (
    !is_numeric($idUsuario) ||
    (int) $idUsuario < 1 ||
    $rol !== "cliente"
) {

    responder(
        false,
        "Debes iniciar sesión para usar favoritos.",
        [
            "requiere_login" => true
        ],
        401
    );
}

$idUsuario = (int) $idUsuario;


// =========================================
// CONSULTAR ESTADO
// GET ?producto=1
// =========================================

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $idProducto = filter_input(
        INPUT_GET,
        "producto",
        FILTER_VALIDATE_INT
    );

    if (!$idProducto || $idProducto < 1) {

        responder(
            false,
            "El producto indicado no es válido.",
            [],
            400
        );
    }


    $stmt = $conexion->prepare("
        SELECT id_favorito
        FROM favoritos
        WHERE id_usuario = ?
          AND id_producto = ?
        LIMIT 1
    ");

    if (!$stmt) {

        responder(
            false,
            "No fue posible consultar favoritos.",
            [],
            500
        );
    }


    $stmt->bind_param(
        "ii",
        $idUsuario,
        $idProducto
    );

    $stmt->execute();

    $resultado = $stmt->get_result();

    $esFavorito =
        $resultado->num_rows > 0;

    $stmt->close();


    responder(
        true,
        "Estado consultado correctamente.",
        [
            "favorito" => $esFavorito
        ]
    );
}


// =========================================
// AGREGAR / QUITAR
// =========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    responder(
        false,
        "Método no permitido.",
        [],
        405
    );
}


// =========================================
// CSRF
// =========================================

$csrf =
    $_POST["csrf"] ?? "";

$csrfSesion =
    $_SESSION["csrf_favoritos"] ?? "";

if (
    !is_string($csrf) ||
    !is_string($csrfSesion) ||
    $csrfSesion === "" ||
    !hash_equals($csrfSesion, $csrf)
) {

    responder(
        false,
        "La solicitud no es válida. Recarga la página.",
        [],
        403
    );
}


// =========================================
// PRODUCTO
// =========================================

$idProducto =
    filter_input(
        INPUT_POST,
        "producto",
        FILTER_VALIDATE_INT
    );

if (!$idProducto || $idProducto < 1) {

    responder(
        false,
        "El producto indicado no es válido.",
        [],
        400
    );
}


// =========================================
// COMPROBAR QUE PRODUCTO EXISTE
// =========================================

$stmtProducto = $conexion->prepare("
    SELECT id_producto
    FROM productos
    WHERE id_producto = ?
      AND estado = 1
    LIMIT 1
");

if (!$stmtProducto) {

    responder(
        false,
        "No fue posible validar el producto.",
        [],
        500
    );
}

$stmtProducto->bind_param(
    "i",
    $idProducto
);

$stmtProducto->execute();

$resultadoProducto =
    $stmtProducto->get_result();

if ($resultadoProducto->num_rows === 0) {

    $stmtProducto->close();

    responder(
        false,
        "El producto no está disponible.",
        [],
        404
    );
}

$stmtProducto->close();


// =========================================
// COMPROBAR SI YA ES FAVORITO
// =========================================

$stmtFavorito = $conexion->prepare("
    SELECT id_favorito
    FROM favoritos
    WHERE id_usuario = ?
      AND id_producto = ?
    LIMIT 1
");

if (!$stmtFavorito) {

    responder(
        false,
        "No fue posible consultar favoritos.",
        [],
        500
    );
}

$stmtFavorito->bind_param(
    "ii",
    $idUsuario,
    $idProducto
);

$stmtFavorito->execute();

$resultadoFavorito =
    $stmtFavorito->get_result();

$yaExiste =
    $resultadoFavorito->num_rows > 0;

$stmtFavorito->close();


// =========================================
// SI EXISTE → QUITAR
// =========================================

if ($yaExiste) {

    $stmtEliminar = $conexion->prepare("
        DELETE FROM favoritos
        WHERE id_usuario = ?
          AND id_producto = ?
        LIMIT 1
    ");

    if (!$stmtEliminar) {

        responder(
            false,
            "No fue posible quitar el favorito.",
            [],
            500
        );
    }

    $stmtEliminar->bind_param(
        "ii",
        $idUsuario,
        $idProducto
    );

    $stmtEliminar->execute();

    $stmtEliminar->close();


    responder(
        true,
        "Producto eliminado de favoritos.",
        [
            "favorito" => false
        ]
    );
}


// =========================================
// SI NO EXISTE → AGREGAR
// =========================================

$stmtInsertar = $conexion->prepare("
    INSERT INTO favoritos (
        id_usuario,
        id_producto
    )
    VALUES (?, ?)
");

if (!$stmtInsertar) {

    responder(
        false,
        "No fue posible agregar el favorito.",
        [],
        500
    );
}

$stmtInsertar->bind_param(
    "ii",
    $idUsuario,
    $idProducto
);

if (!$stmtInsertar->execute()) {

    $stmtInsertar->close();

    responder(
        false,
        "No fue posible agregar el producto a favoritos.",
        [],
        500
    );
}

$stmtInsertar->close();


responder(
    true,
    "Producto agregado a favoritos.",
    [
        "favorito" => true
    ]
);