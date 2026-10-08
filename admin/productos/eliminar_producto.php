<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
if (!agranda_admin_autorizado()) {
    header('Location: ../login.php');
    exit();
}
require_once __DIR__ . '/seguridad.php';
agranda_productos_exigir_post();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    $_SESSION['productos_mensaje'] = 'El identificador del producto no es válido.';
    header('Location: productos.php', true, 303);
    exit();
}
require_once __DIR__ . '/../../config/conexion.php';
try {
    $conexion->begin_transaction();
    $stmt = $conexion->prepare('SELECT id_producto FROM productos WHERE id_producto = ? FOR UPDATE');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$existe) {
        throw new RuntimeException('El producto ya no existe.');
    }
    // Conserva las restricciones existentes: estas relaciones no se eliminan.
    foreach (['detalle_carrito', 'detalle_ingreso', 'detalle_pedido', 'favoritos',
              'movimientos_inventario', 'producto_atributo', 'promocion_producto', 'resenas'] as $tabla) {
        $stmt = $conexion->prepare("SELECT id_producto FROM ".$tabla." WHERE id_producto = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $relacionado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($relacionado) {
            throw new RuntimeException('No se puede eliminar el producto porque tiene registros relacionados. Puedes mantenerlo inactivo desde Editar.');
        }
    }
    // Únicamente las relaciones que el controlador anterior ya retiraba.
    foreach (['imagenes_producto', 'inventario', 'proveedor_producto', 'compatibilidades', 'productos'] as $tabla) {
        $stmt = $conexion->prepare("DELETE FROM ".$tabla." WHERE id_producto = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) { throw new mysqli_sql_exception('No se pudo completar la eliminación.'); }
        if ($tabla === 'productos' && $stmt->affected_rows !== 1) {
            throw new RuntimeException('El producto ya no existe.');
        }
        $stmt->close();
    }
    $conexion->commit();
    $_SESSION['productos_mensaje'] = 'Producto eliminado correctamente.';
} catch (Throwable $e) {
    $conexion->rollback();
    error_log('Eliminación de producto: ' . $e->getMessage());
    $_SESSION['productos_mensaje'] = $e instanceof RuntimeException && !($e instanceof mysqli_sql_exception)
        ? $e->getMessage()
        : 'No fue posible eliminar el producto por sus relaciones o por un error. No se eliminaron datos.';
}
// Los archivos físicos de imágenes se conservan deliberadamente.
header('Location: productos.php', true, 303);
exit();
