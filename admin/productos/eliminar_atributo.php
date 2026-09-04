<?php

session_start();

// =================================
// VERIFICAR SESIÓN
// =================================

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

// =================================
// VALIDAR ID
// =================================

$idAtributo = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($idAtributo === false || $idAtributo === null || $idAtributo < 1) {
    header("Location: atributos.php");
    exit();
}

// =================================
// OBTENER ATRIBUTO
// =================================

$sql = "SELECT id_atributo, nombre
        FROM atributos_producto
        WHERE id_atributo = ?
        LIMIT 1";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    header("Location: atributos.php");
    exit();
}

$stmt->bind_param("i", $idAtributo);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {

    $stmt->close();

    header("Location: atributos.php");
    exit();
}

$atributo = $resultado->fetch_assoc();

$stmt->close();

// =================================
// ELIMINAR ATRIBUTO
// =================================

try {

    // Iniciar transacción
    $conexion->begin_transaction();


    // =================================
    // ELIMINAR RELACIONES CON PRODUCTOS
    // =================================

    $sql = "DELETE FROM producto_atributo
            WHERE id_atributo = ?";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "No fue posible preparar la eliminación de las relaciones."
        );
    }

    $stmt->bind_param("i", $idAtributo);

    if (!$stmt->execute()) {

        $stmt->close();

        throw new Exception(
            "No fue posible eliminar las relaciones del atributo."
        );
    }

    $stmt->close();


    // =================================
    // ELIMINAR ATRIBUTO
    // =================================

    $sql = "DELETE FROM atributos_producto
            WHERE id_atributo = ?";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "No fue posible preparar la eliminación del atributo."
        );
    }

    $stmt->bind_param("i", $idAtributo);

    if (!$stmt->execute()) {

        $stmt->close();

        throw new Exception(
            "No fue posible eliminar la característica."
        );
    }

    // Verificar que realmente se haya eliminado
    if ($stmt->affected_rows !== 1) {

        $stmt->close();

        throw new Exception(
            "La característica no pudo ser eliminada."
        );
    }

    $stmt->close();


    // =================================
    // CONFIRMAR TRANSACCIÓN
    // =================================

    $conexion->commit();


    // =================================
    // VOLVER A ATRIBUTOS
    // =================================

    header("Location: atributos.php");
    exit();


} catch (Exception $e) {

    // =================================
    // DESHACER CAMBIOS
    // =================================

    $conexion->rollback();

    // En desarrollo mostramos el error.
    // Posteriormente podemos cambiarlo por
    // un mensaje visual para el administrador.

    die(
        "No fue posible eliminar la característica. " .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        )
    );
}