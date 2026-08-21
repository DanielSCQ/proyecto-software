<?php

session_start();

// Verificar sesión
if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");
    exit();

}
require_once("../../config/conexion.php");

// Verificar que llegue ID

if (isset($_GET["id"])) {

    $id_producto = $_GET["id"];

    // Eliminar imagen asociada

    $sql = "DELETE FROM imagenes_producto 
            WHERE id_producto = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();

    // Eliminar inventario

    $sql = "DELETE FROM inventario 
            WHERE id_producto = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();

    // Eliminar relación con proveedores

    $sql = "DELETE FROM proveedor_producto 
            WHERE id_producto = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();

    // Eliminar compatibilidades

    $sql = "DELETE FROM compatibilidades 
            WHERE id_producto = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();

    // Finalmente eliminar producto
    $sql = "DELETE FROM productos 
            WHERE id_producto = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_producto);

    if($stmt->execute()){

        header("Location: productos.php");
        exit();

    }else{
        echo "Error al eliminar el producto.";
    }

}else{
    header("Location: productos.php");
    exit();
}
?>