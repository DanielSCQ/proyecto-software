<?php

session_start();

/*
=================================
    VERIFICAR SESIÓN
=================================
*/

if (!isset($_SESSION["id_usuario"])) {

    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "exito" => false,
        "mensaje" => "La sesión ha expirado."
    ]);
    exit();

}


/*
=================================
    CONEXIÓN
=================================
*/

require_once("../../config/conexion.php");


/*
=================================
    RECIBIR DATOS
=================================
*/

$datos = json_decode(
    file_get_contents("php://input"),
    true
);


if (!is_array($datos)) {

    echo json_encode([
        "exito" => false,
        "mensaje" => "Los datos recibidos no son válidos."
    ]);

    exit();

}


/*
=================================
    ID CATEGORÍA ORIGINAL
=================================
*/

$idCategoria = $datos["id_categoria"] ?? null;


if (
    $idCategoria === null ||
    !filter_var($idCategoria, FILTER_VALIDATE_INT) ||
    (int)$idCategoria <= 0
) {

    echo json_encode([
        "exito" => false,
        "mensaje" => "La categoría original no es válida."
    ]);

    exit();

}


$idCategoria = (int)$idCategoria;


/*
=================================
    CAMBIOS
=================================
*/

$cambios = $datos["cambios"] ?? null;


if (!is_array($cambios) || empty($cambios)) {

    echo json_encode([
        "exito" => false,
        "mensaje" => "No se recibieron productos para reorganizar."
    ]);

    exit();

}


/*
=================================
    INICIAR TRANSACCIÓN
=================================
*/

$conexion->begin_transaction();


try {


    /*
    =================================
        PREPARAR ACTUALIZACIÓN
    =================================
    */

    $sqlActualizar = "
        UPDATE productos
        SET id_categoria = ?
        WHERE id_producto = ?
        AND id_categoria = ?
    ";

    $stmtActualizar = $conexion->prepare($sqlActualizar);


    if (!$stmtActualizar) {

        throw new Exception(
            "No fue posible preparar la actualización de productos."
        );

    }


    /*
    =================================
        PROCESAR PRODUCTOS
    =================================
    */

    foreach ($cambios as $cambio) {


        $idProducto =
            $cambio["id_producto"] ?? null;

        $nuevaCategoria =
            $cambio["id_categoria"] ?? null;


        /*
        ==============================
            VALIDAR ID PRODUCTO
        ==============================
        */

        if (
            $idProducto === null ||
            !filter_var(
                $idProducto,
                FILTER_VALIDATE_INT
            ) ||
            (int)$idProducto <= 0
        ) {

            throw new Exception(
                "Se recibió un producto no válido."
            );

        }


        /*
        ==============================
            VALIDAR NUEVA CATEGORÍA
        ==============================
        */

        if (
            $nuevaCategoria === null ||
            !filter_var(
                $nuevaCategoria,
                FILTER_VALIDATE_INT
            ) ||
            (int)$nuevaCategoria <= 0
        ) {

            throw new Exception(
                "Debes seleccionar una categoría válida para todos los productos."
            );

        }


        $idProducto =
            (int)$idProducto;

        $nuevaCategoria =
            (int)$nuevaCategoria;


        /*
        ==============================
            NO PERMITIR MISMA CATEGORÍA
        ==============================
        */

        if ($nuevaCategoria === $idCategoria) {

            throw new Exception(
                "Un producto no puede ser reasignado a la misma categoría que se está eliminando."
            );

        }


        /*
        ==============================
            COMPROBAR QUE LA NUEVA
            CATEGORÍA EXISTA
        ==============================
        */

        $sqlCategoria = "
            SELECT id_categoria
            FROM categorias
            WHERE id_categoria = ?
        ";

        $stmtCategoria =
            $conexion->prepare($sqlCategoria);


        if (!$stmtCategoria) {

            throw new Exception(
                "No fue posible verificar la nueva categoría."
            );

        }


        $stmtCategoria->bind_param(
            "i",
            $nuevaCategoria
        );

        $stmtCategoria->execute();

        $resultadoCategoria =
            $stmtCategoria->get_result();


        if (!$resultadoCategoria->fetch_assoc()) {

            $stmtCategoria->close();

            throw new Exception(
                "La categoría seleccionada no existe."
            );

        }


        $stmtCategoria->close();


        /*
        ==============================
            ACTUALIZAR PRODUCTO
        ==============================
        */

        $stmtActualizar->bind_param(
            "iii",
            $nuevaCategoria,
            $idProducto,
            $idCategoria
        );


        if (!$stmtActualizar->execute()) {

            throw new Exception(
                "No fue posible actualizar uno de los productos."
            );

        }


        /*
        ==============================
            COMPROBAR QUE REALMENTE
            SE ACTUALIZÓ
        ==============================
        */

        if ($stmtActualizar->affected_rows !== 1) {

            throw new Exception(
                "Uno de los productos no pertenece a la categoría que se está eliminando."
            );

        }

    }


    $stmtActualizar->close();


    /*
    =================================
        COMPROBAR QUE NO QUEDEN
        PRODUCTOS EN LA CATEGORÍA
    =================================
    */

    $sqlComprobar = "
        SELECT COUNT(*) AS total
        FROM productos
        WHERE id_categoria = ?
    ";

    $stmtComprobar =
        $conexion->prepare($sqlComprobar);


    if (!$stmtComprobar) {

        throw new Exception(
            "No fue posible comprobar los productos restantes."
        );

    }


    $stmtComprobar->bind_param(
        "i",
        $idCategoria
    );

    $stmtComprobar->execute();

    $resultadoComprobar =
        $stmtComprobar->get_result();

    $fila =
        $resultadoComprobar->fetch_assoc();

    $stmtComprobar->close();


    if ((int)$fila["total"] > 0) {

        throw new Exception(
            "Todavía existen productos asociados a esta categoría."
        );

    }


    /*
    =================================
        ELIMINAR CATEGORÍA
    =================================
    */

    $sqlEliminar = "
        DELETE FROM categorias
        WHERE id_categoria = ?
    ";

    $stmtEliminar =
        $conexion->prepare($sqlEliminar);


    if (!$stmtEliminar) {

        throw new Exception(
            "No fue posible preparar la eliminación de la categoría."
        );

    }


    $stmtEliminar->bind_param(
        "i",
        $idCategoria
    );


    if (!$stmtEliminar->execute()) {

        throw new Exception(
            "No fue posible eliminar la categoría."
        );

    }


    if ($stmtEliminar->affected_rows !== 1) {

        throw new Exception(
            "La categoría no pudo ser eliminada."
        );

    }


    $stmtEliminar->close();


    /*
    =================================
        CONFIRMAR TRANSACCIÓN
    =================================
    */

    $conexion->commit();


    echo json_encode([
        "exito" => true,
        "mensaje" => "Los productos fueron reorganizados y la categoría fue eliminada correctamente."
    ]);

    exit();


} catch (Throwable $e) {


    /*
    =================================
        DESHACER TODO
    =================================
    */

    $conexion->rollback();


    echo json_encode([
        "exito" => false,
        "mensaje" => $e->getMessage()
    ]);

    exit();

}

?>