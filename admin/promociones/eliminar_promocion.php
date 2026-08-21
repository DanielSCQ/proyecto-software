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
// OBTENER ID DE LA PROMOCIÓN
// =================================

$id_promocion = intval($_GET["id"] ?? 0);

if ($id_promocion <= 0) {
    header("Location: promociones.php");
    exit();
}


// =================================
// VERIFICAR QUE LA PROMOCIÓN EXISTA
// =================================

$sqlVerificar = "SELECT id_promocion
                 FROM promociones
                 WHERE id_promocion = ?";

$stmtVerificar = $conexion->prepare($sqlVerificar);

if (!$stmtVerificar) {
    die("Error en la consulta.");
}

$stmtVerificar->bind_param("i", $id_promocion);

$stmtVerificar->execute();

$resultado = $stmtVerificar->get_result();

if ($resultado->num_rows === 0) {

    header("Location: promociones.php");
    exit();

}

$stmtVerificar->close();


// =================================
// INICIAR TRANSACCIÓN
// =================================

$conexion->begin_transaction();


try {

    // =================================
    // ELIMINAR RELACIONES
    // =================================

    $sqlRelacion = "DELETE FROM promocion_producto
                    WHERE id_promocion = ?";

    $stmtRelacion = $conexion->prepare($sqlRelacion);

    if (!$stmtRelacion) {
        throw new Exception(
            "Error al preparar la eliminación de relaciones."
        );
    }

    $stmtRelacion->bind_param(
        "i",
        $id_promocion
    );

    if (!$stmtRelacion->execute()) {

        throw new Exception(
            "No se pudieron eliminar los productos asociados."
        );

    }

    $stmtRelacion->close();


    // =================================
    // ELIMINAR PROMOCIÓN
    // =================================

    $sqlPromocion = "DELETE FROM promociones
                     WHERE id_promocion = ?";

    $stmtPromocion = $conexion->prepare($sqlPromocion);

    if (!$stmtPromocion) {
        throw new Exception(
            "Error al preparar la eliminación de la promoción."
        );
    }

    $stmtPromocion->bind_param(
        "i",
        $id_promocion
    );

    if (!$stmtPromocion->execute()) {

        throw new Exception(
            "No se pudo eliminar la promoción."
        );

    }

    $stmtPromocion->close();


    // =================================
    // CONFIRMAR CAMBIOS
    // =================================

    $conexion->commit();


    // =================================
    // VOLVER A PROMOCIONES
    // =================================

    header("Location: promociones.php");

    exit();


} catch (Exception $e) {

    // =================================
    // DESHACER CAMBIOS
    // =================================

    $conexion->rollback();


    echo "
    <div style='
        font-family: Arial;
        padding: 30px;
    '>

        <h2>Error al eliminar la promoción</h2>

        <p>"
        . htmlspecialchars($e->getMessage())
        . "</p>

        <a href='promociones.php'>
            Volver a promociones
        </a>

    </div>";

}

?>