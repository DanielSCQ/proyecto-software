<?php

session_start();

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once("../config/conexion.php");

// Verificar que llegue el ID
if (isset($_GET["id"])) {

    $id = $_GET["id"];

    $sql = "DELETE FROM categorias WHERE id_categoria = ?";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        header("Location: categorias.php");
        exit();

    } else {

        echo "Error al eliminar la categoría.";

    }

} else {

    header("Location: categorias.php");
    exit();

}

?>