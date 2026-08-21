<?php

session_start();

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");
    exit();

}

// Mostrar errores (solo durante el desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexión a la base de datos
require_once("../../config/conexion.php");

// Verificar que el formulario se haya enviado por POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: productos.php");
    exit();

}

// Verificar la acción
if (!isset($_POST["accion"]) || $_POST["accion"] != "agregar") {

    header("Location: productos.php");
    exit();

}

// Recibir los datos del formulario

$nombre = trim($_POST["nombre"]);
$codigo_producto = trim($_POST["codigo_producto"]);
$id_categoria = $_POST["id_categoria"];
$id_marca = $_POST["id_marca"];
$id_proveedor = $_POST["id_proveedor"];
$precio = $_POST["precio"];
$precio_compra = $_POST["precio_compra"];
$stockActual = $_POST["stock_actual"];
$stockMinimo = $_POST["stock_minimo"];
$peso = !empty($_POST["peso"]) ? $_POST["peso"] : NULL;
$descripcion = trim($_POST["descripcion"]);
$estado = $_POST["estado"];
$destacado = $_POST["destacado"];

// Validar los campos obligatorios

if (
    empty($nombre) ||
    empty($codigo_producto) ||
    empty($id_categoria) ||
    empty($id_marca) ||
    empty($id_proveedor) ||
    empty($precio) ||
    empty($precio_compra)
) {

    die("Error: Debe completar todos los campos obligatorios.");

}

// Insertar el producto

$sql = "INSERT INTO productos
(
    id_categoria,
    id_marca,
    nombre,
    descripcion,
    codigo_producto,
    precio,
    peso,
    estado,
    destacado
)
VALUES
(
    ?, ?, ?, ?, ?, ?, ?, ?, ?
)";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "iisssdsii",
    $id_categoria,
    $id_marca,
    $nombre,
    $descripcion,
    $codigo_producto,
    $precio,
    $peso,
    $estado,
    $destacado
);

if (!$stmt->execute()) {

    die("Error al guardar el producto: " . $stmt->error);

}

// Obtener el ID del producto recién creado
$id_producto = $conexion->insert_id;

// Relacionar el producto con el proveedor

$sqlProveedor = "INSERT INTO proveedor_producto
(
    id_proveedor,
    id_producto,
    precio_compra
)
VALUES
(
    ?, ?, ?
)";

$stmtProveedor = $conexion->prepare($sqlProveedor);

$stmtProveedor->bind_param(
    "iid",
    $id_proveedor,
    $id_producto,
    $precio_compra
);

if (!$stmtProveedor->execute()) {

    die("Error al guardar el proveedor del producto: " . $stmtProveedor->error);

}

// Crear el registro en inventario

$sqlInventario = "INSERT INTO inventario
(
    id_producto,
    stock_actual,
    stock_minimo
)
VALUES
(
    ?, ?, ?
)";

$stmtInventario = $conexion->prepare($sqlInventario);

$stmtInventario->bind_param(
    "iii",
    $id_producto,
    $stockActual,
    $stockMinimo
);

if (!$stmtInventario->execute()) {

    die("Error al crear el inventario: " . $stmtInventario->error);

}

// Guardar la imagen del producto (si se seleccionó una)

if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] == 0) {

    $carpeta = "../../uploads/productos/";

        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $nombreImagen = time() . "_" . basename($_FILES["imagen"]["name"]);

        $rutaFisica = $carpeta . $nombreImagen;

        $rutaImagen = "uploads/productos/" . $nombreImagen;

    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaFisica)) {

        $sqlImagen = "INSERT INTO imagenes_producto
        (
            id_producto,
            ruta_imagen,
            principal
        )
        VALUES
        (
            ?, ?, 1
        )";

        $stmtImagen = $conexion->prepare($sqlImagen);

        $stmtImagen->bind_param(
            "is",
            $id_producto,
            $rutaImagen
        );

        if (!$stmtImagen->execute()) {

            die("Error al guardar la imagen: " . $stmtImagen->error);

        }

    }

}

header("Location: productos.php");
exit();