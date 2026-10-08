<?php

session_start();

// =================================
// VERIFICAR SESIÓN
// =================================

require_once __DIR__ . "/../includes/auth.php";

if (!agranda_admin_autorizado()) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/seguridad.php';
agranda_productos_exigir_post();
require_once("../../config/conexion.php");

// =================================
// VALIDAR ID
// =================================

$idAtributo = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($idAtributo === false || $idAtributo === null || $idAtributo < 1) {
    header("Location: atributos.php", true, 303);
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
    $bloqueo = $conexion->prepare("SELECT id_atributo FROM atributos_producto WHERE id_atributo = ? FOR UPDATE");
    $bloqueo->bind_param("i", $idAtributo);
    $bloqueo->execute();
    if (!$bloqueo->get_result()->fetch_assoc()) { throw new RuntimeException("Atributo inexistente."); }
    $bloqueo->close();


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

    header("Location: atributos.php", true, 303);
    exit();


} catch (Throwable $e) {

    // =================================
    // DESHACER CAMBIOS
    // =================================

    $conexion->rollback();

    error_log('Error al eliminar atributo: ' . $e->getMessage());
    $_SESSION['productos_mensaje'] = 'No fue posible eliminar la característica. No se eliminaron sus asociaciones.';
    header('Location: atributos.php', true, 303);
    exit();
}
