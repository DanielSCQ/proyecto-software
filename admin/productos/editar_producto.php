<?php

session_start();

// =================================
// VERIFICAR SESIÓN
// =================================

if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");
    exit();

}

// =================================
// MOSTRAR ERRORES - SOLO DESARROLLO
// =================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =================================
// CONEXIÓN
// =================================

require_once("../../config/conexion.php");

// =================================
// VERIFICAR ID DEL PRODUCTO
// =================================

if (!isset($_GET["id"]) || !ctype_digit($_GET["id"])) {

    header("Location: productos.php");
    exit();

}

$id_producto = (int) $_GET["id"];


// =================================
// OBTENER PRODUCTO ACTUAL
// =================================

$sql = "SELECT * FROM productos WHERE id_producto = ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {

    die("No fue posible preparar la consulta del producto.");

}

$stmt->bind_param("i", $id_producto);

$stmt->execute();

$resultado = $stmt->get_result();

$producto = $resultado->fetch_assoc();

$stmt->close();


// =================================
// VERIFICAR QUE EL PRODUCTO EXISTA
// =================================

if (!$producto) {

    header("Location: productos.php");
    exit();

}


// =================================
// VALORES INICIALES
// =================================

$nombre = $producto["nombre"];
$descripcion = $producto["descripcion"];
$codigo_producto = $producto["codigo_producto"];
$id_categoria = (string) $producto["id_categoria"];
$id_marca = (string) $producto["id_marca"];
$precio = $producto["precio"];
$peso = $producto["peso"];
$estado = (string) $producto["estado"];
$destacado = (string) $producto["destacado"];

$errores = [];


// =================================
// ACTUALIZAR PRODUCTO
// =================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // =================================
    // RECIBIR DATOS
    // =================================

    $nombre = trim($_POST["nombre"] ?? "");

    $descripcion = trim($_POST["descripcion"] ?? "");

    $codigo_producto = trim($_POST["codigo_producto"] ?? "");

    $id_categoria = $_POST["id_categoria"] ?? "";

    $id_marca = $_POST["id_marca"] ?? "";

    $precio = trim($_POST["precio"] ?? "");

    $peso = trim($_POST["peso"] ?? "");

    $estado = $_POST["estado"] ?? "";

    $destacado = $_POST["destacado"] ?? "";


    // =================================
    // VALIDAR NOMBRE
    // =================================

    if ($nombre === "") {

        $errores[] = "El nombre del producto es obligatorio.";

    } elseif (mb_strlen($nombre) > 150) {

        $errores[] = "El nombre del producto no puede superar los 150 caracteres.";

    }

    // =================================
    // VALIDAR CÓDIGO DEL PRODUCTO
    // =================================

    if ($codigo_producto === "") {

        $errores[] = "El código del producto es obligatorio.";

    } elseif (mb_strlen($codigo_producto) > 50) {

        $errores[] = "El código del producto no puede superar los 50 caracteres.";

    }

    // =================================
    // VALIDAR DESCRIPCIÓN
    // =================================

    if (mb_strlen($descripcion) > 300) {

        $errores[] = "La descripción no puede superar los 300 caracteres.";

    }

    // =================================
    // VALIDAR CATEGORÍA
    // =================================

    if (!filter_var($id_categoria, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1]
    ])) {

        $errores[] = "La categoría seleccionada no es válida.";

    } else {

        $id_categoria = (int) $id_categoria;

    }

    // =================================
    // VALIDAR MARCA
    // =================================

    if (!filter_var($id_marca, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1]
    ])) {

        $errores[] = "La marca seleccionada no es válida.";

    } else {

        $id_marca = (int) $id_marca;

    }

    // =================================
    // VALIDAR PRECIO
    // =================================

    if ($precio === "") {

        $errores[] = "El precio de venta es obligatorio.";

    } elseif (!is_numeric($precio)) {

        $errores[] = "El precio de venta debe ser un número válido.";

    } elseif ((float) $precio < 0) {

        $errores[] = "El precio de venta no puede ser negativo.";

    } elseif (strpos($precio, "e") !== false || strpos($precio, "E") !== false) {

        $errores[] = "El precio de venta no tiene un formato válido.";

    } else {

        $partesPrecio = explode(".", $precio);

        $parteEnteraPrecio = $partesPrecio[0];

        $parteDecimalPrecio = $partesPrecio[1] ?? "";

        if (strlen($parteEnteraPrecio) > 8) {

            $errores[] = "El precio de venta es demasiado grande.";

        }

        if (strlen($parteDecimalPrecio) > 2) {

            $errores[] = "El precio de venta solo puede tener máximo 2 decimales.";

        }

        if (
            strlen($parteEnteraPrecio) <= 8 &&
            strlen($parteDecimalPrecio) <= 2
        ) {

            $precio = (float) $precio;

        }

    }

    // =================================
    // VALIDAR PESO
    // =================================

    if ($peso !== "") {

        if (!is_numeric($peso)) {

            $errores[] = "El peso debe ser un número válido.";

        } elseif ((float) $peso < 0) {

            $errores[] = "El peso no puede ser negativo.";

        } elseif (strpos($peso, "e") !== false || strpos($peso, "E") !== false) {

            $errores[] = "El peso no tiene un formato válido.";

        } else {

            $partesPeso = explode(".", $peso);

            $parteEnteraPeso = $partesPeso[0];

            $parteDecimalPeso = $partesPeso[1] ?? "";

            if (strlen($parteEnteraPeso) > 6) {

                $errores[] = "El peso es demasiado grande.";

            }

            if (strlen($parteDecimalPeso) > 2) {

                $errores[] = "El peso solo puede tener máximo 2 decimales.";

            }

            if (
                strlen($parteEnteraPeso) <= 6 &&
                strlen($parteDecimalPeso) <= 2
            ) {

                $peso = (float) $peso;

            }

        }

    } else {

        $peso = null;

    }

    // =================================
    // VALIDAR ESTADO
    // =================================

    if ($estado !== "0" && $estado !== "1") {

        $errores[] = "El estado seleccionado no es válido.";

    }


    // =================================
    // VALIDAR DESTACADO
    // =================================

    if ($destacado !== "0" && $destacado !== "1") {

        $errores[] = "El valor de producto destacado no es válido.";

    }


    // =================================
    // VALIDAR CATEGORÍA EN BD
    // =================================

    if (empty($errores)) {

        $sqlCategoria = "
            SELECT id_categoria
            FROM categorias
            WHERE id_categoria = ?
            AND estado = 1
            LIMIT 1
        ";

        $stmtCategoria = $conexion->prepare($sqlCategoria);

        if (!$stmtCategoria) {

            $errores[] = "No fue posible verificar la categoría.";

        } else {

            $stmtCategoria->bind_param("i", $id_categoria);

            $stmtCategoria->execute();

            $resultadoCategoria = $stmtCategoria->get_result();

            if ($resultadoCategoria->num_rows === 0) {

                $errores[] = "La categoría seleccionada no existe o está inactiva.";

            }

            $stmtCategoria->close();

        }

    }

    // =================================
    // VALIDAR MARCA EN BD
    // =================================

    if (empty($errores)) {

        $sqlMarca = "
            SELECT id_marca
            FROM marcas
            WHERE id_marca = ?
            AND estado = 1
            LIMIT 1
        ";

        $stmtMarca = $conexion->prepare($sqlMarca);

        if (!$stmtMarca) {

            $errores[] = "No fue posible verificar la marca.";

        } else {

            $stmtMarca->bind_param("i", $id_marca);

            $stmtMarca->execute();

            $resultadoMarca = $stmtMarca->get_result();

            if ($resultadoMarca->num_rows === 0) {

                $errores[] = "La marca seleccionada no existe o está inactiva.";

            }

            $stmtMarca->close();

        }

    }

    // =================================
    // VALIDAR CÓDIGO ÚNICO
    // =================================
    // IMPORTANTE:
    // Se excluye el producto actual.
    // =================================

    if (empty($errores)) {

        $sqlCodigo = "
            SELECT id_producto
            FROM productos
            WHERE codigo_producto = ?
            AND id_producto != ?
            LIMIT 1
        ";

        $stmtCodigo = $conexion->prepare($sqlCodigo);

        if (!$stmtCodigo) {

            $errores[] = "No fue posible verificar el código del producto.";

        } else {

            $stmtCodigo->bind_param(
                "si",
                $codigo_producto,
                $id_producto
            );

            $stmtCodigo->execute();

            $resultadoCodigo = $stmtCodigo->get_result();

            if ($resultadoCodigo->num_rows > 0) {

                $errores[] = "Ya existe otro producto con ese código.";

            }

            $stmtCodigo->close();

        }

    }

    // =================================
    // VALIDAR IMAGEN
    // =================================

    $archivoImagen = null;
    $tipoMime = null;

    if (
        isset($_FILES["imagen"]) &&
        $_FILES["imagen"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {

            $errores[] = "Ocurrió un error al subir la imagen.";

        } else {

            $archivoImagen = $_FILES["imagen"];

            // Máximo 2 MB
            $tamanoMaximo = 2 * 1024 * 1024;

            if ($archivoImagen["size"] > $tamanoMaximo) {

                $errores[] = "La imagen no puede superar los 2 MB.";

            }

            // =================================
            // VERIFICAR MIME REAL
            // =================================

            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo === false) {

                $errores[] = "No fue posible verificar el tipo de imagen.";

            } else {

                $tipoMime = finfo_file(
                    $finfo,
                    $archivoImagen["tmp_name"]
                );

                finfo_close($finfo);


                $tiposPermitidos = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];


                if (!in_array($tipoMime, $tiposPermitidos, true)) {

                    $errores[] = "La imagen debe ser JPG, JPEG, PNG o WEBP.";

                }

            }

        }

    }

    // =================================
    // SI NO HAY ERRORES → ACTUALIZAR
    // =================================

    if (empty($errores)) {

        $conexion->begin_transaction();

        $rutaDestino = null;
        $rutaImagen = null;
        $imagenMovida = false;

        try {

            // =================================
            // ACTUALIZAR PRODUCTO
            // =================================

            $sql = "
                UPDATE productos
                SET
                    id_categoria = ?,
                    id_marca = ?,
                    nombre = ?,
                    descripcion = ?,
                    codigo_producto = ?,
                    precio = ?,
                    peso = ?,
                    estado = ?,
                    destacado = ?
                WHERE id_producto = ?
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {

                throw new Exception(
                    "No fue posible preparar la actualización del producto."
                );

            }


            $stmt->bind_param(
                "iisssddiii",
                $id_categoria,
                $id_marca,
                $nombre,
                $descripcion,
                $codigo_producto,
                $precio,
                $peso,
                $estado,
                $destacado,
                $id_producto
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "No fue posible actualizar el producto."
                );

            }

            $stmt->close();

            // =================================
            // ACTUALIZAR IMAGEN SI SE SUBIÓ
            // =================================

            if ($archivoImagen !== null) {

                $carpeta = "../../uploads/productos/";


                if (!is_dir($carpeta)) {

                    if (!mkdir($carpeta, 0755, true)) {

                        throw new Exception(
                            "No fue posible crear la carpeta de imágenes."
                        );

                    }

                }

                // =================================
                // GENERAR EXTENSIÓN SEGURA
                // =================================

                $extension = match ($tipoMime) {

                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"

                };

                // =================================
                // GENERAR NOMBRE ALEATORIO
                // =================================

                $nombreImagen =
                    bin2hex(random_bytes(16))
                    . "."
                    . $extension;


                $rutaDestino =
                    $carpeta . $nombreImagen;


                $rutaImagen =
                    "uploads/productos/" . $nombreImagen;

                // =================================
                // MOVER ARCHIVO
                // =================================

                if (!move_uploaded_file(
                    $archivoImagen["tmp_name"],
                    $rutaDestino
                )) {

                    throw new Exception(
                        "No fue posible guardar la nueva imagen."
                    );

                }

                $imagenMovida = true;

                // =================================
                // BUSCAR IMAGEN PRINCIPAL
                // =================================

                $sqlBuscar = "
                    SELECT id_imagen, ruta_imagen
                    FROM imagenes_producto
                    WHERE id_producto = ?
                    AND principal = 1
                    LIMIT 1
                ";

                $stmtBuscar = $conexion->prepare($sqlBuscar);

                if (!$stmtBuscar) {

                    throw new Exception(
                        "No fue posible verificar la imagen actual."
                    );

                }

                $stmtBuscar->bind_param(
                    "i",
                    $id_producto
                );

                $stmtBuscar->execute();

                $resultadoImagen = $stmtBuscar->get_result();

                $imagenActual = $resultadoImagen->fetch_assoc();

                $stmtBuscar->close();

                // =================================
                // ACTUALIZAR O INSERTAR IMAGEN
                // =================================

                if ($imagenActual) {

                    $sqlActualizarImagen = "
                        UPDATE imagenes_producto
                        SET ruta_imagen = ?
                        WHERE id_imagen = ?
                    ";

                    $stmtActualizarImagen =
                        $conexion->prepare(
                            $sqlActualizarImagen
                        );


                    if (!$stmtActualizarImagen) {

                        throw new Exception(
                            "No fue posible preparar la actualización de la imagen."
                        );

                    }


                    $stmtActualizarImagen->bind_param(
                        "si",
                        $rutaImagen,
                        $imagenActual["id_imagen"]
                    );


                    if (!$stmtActualizarImagen->execute()) {

                        throw new Exception(
                            "No fue posible actualizar la imagen."
                        );

                    }

                    $stmtActualizarImagen->close();


                } else {

                    $sqlInsertarImagen = "
                        INSERT INTO imagenes_producto
                        (
                            id_producto,
                            ruta_imagen,
                            principal
                        )
                        VALUES
                        (?, ?, 1)
                    ";

                    $stmtInsertarImagen =
                        $conexion->prepare(
                            $sqlInsertarImagen
                        );


                    if (!$stmtInsertarImagen) {

                        throw new Exception(
                            "No fue posible preparar la imagen."
                        );

                    }


                    $stmtInsertarImagen->bind_param(
                        "is",
                        $id_producto,
                        $rutaImagen
                    );


                    if (!$stmtInsertarImagen->execute()) {

                        throw new Exception(
                            "No fue posible guardar la imagen."
                        );

                    }

                    $stmtInsertarImagen->close();
                }
            }

            // =================================
            // CONFIRMAR TRANSACCIÓN
            // =================================

            $conexion->commit();

            // =================================
            // ELIMINAR IMAGEN FÍSICA ANTERIOR
            // =================================

            if (
                $archivoImagen !== null &&
                isset($imagenActual["ruta_imagen"]) &&
                !empty($imagenActual["ruta_imagen"])
            ) {

                $imagenAnterior =
                    "../../" . $imagenActual["ruta_imagen"];


                if (
                    file_exists($imagenAnterior) &&
                    is_file($imagenAnterior)
                ) {

                    unlink($imagenAnterior);

                }

            }

            // =================================
            // REDIRECCIONAR
            // =================================

            header("Location: productos.php");
            exit();

        } catch (Exception $e) {

            // =================================
            // DESHACER CAMBIOS
            // =================================

            $conexion->rollback();

            // =================================
            // ELIMINAR NUEVA IMAGEN SI YA SE MOVIÓ
            // =================================

            if (
                $imagenMovida &&
                $rutaDestino !== null &&
                file_exists($rutaDestino)
            ) {

                unlink($rutaDestino);

            }

            $errores[] =
                "No fue posible actualizar el producto.";

        }
    }
}

// =================================
// CARGAR CATEGORÍAS
// =================================

$sqlCategorias = "
    SELECT id_categoria, nombre
    FROM categorias
    WHERE estado = 1
    ORDER BY nombre ASC
";

$resultadoCategorias =
    $conexion->query($sqlCategorias);


// =================================
// CARGAR MARCAS
// =================================

$sqlMarcas = "
    SELECT id_marca, nombre
    FROM marcas
    WHERE estado = 1
    ORDER BY nombre ASC
";

$resultadoMarcas =
    $conexion->query($sqlMarcas);


// =================================
// OBTENER IMAGEN ACTUAL
// =================================

$sqlImagen = "
    SELECT ruta_imagen
    FROM imagenes_producto
    WHERE id_producto = ?
    AND principal = 1
    LIMIT 1
";

$stmtImagen = $conexion->prepare($sqlImagen);

$stmtImagen->bind_param(
    "i",
    $id_producto
);

$stmtImagen->execute();

$resultadoImagen =
    $stmtImagen->get_result();

$imagen =
    $resultadoImagen->fetch_assoc();

$stmtImagen->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Editar Producto | AGRANDA</title>

    <link rel="stylesheet"
          href="productos.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- =================================
         MENÚ LATERAL
    ================================= -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>productos</p>

        </div>

        <nav>

            <ul>

                <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="productos.php" class="activo">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                <li><a href="../inventario/inventario.php">📁 Inventario</a></li>

                <li><a href="../pedidos/pedidos.php">🛒 Pedidos</a></li>

                <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>

    <main class="contenido">

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>
                    ¡Bienvenido,
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["nombre"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>!
                </h2>

                <p>Panel de Administración AGRANDA</p>

            </div>

            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>

                <div class="usuario">

                    👤
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["nombre"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>

                </div>

                <a href="../cerrar_sesion.php"
                   class="btn-salir">Cerrar sesión</a>

            </div>

        </header>

        <!-- =================================
             FORMULARIO
        ================================= -->

        <div class="principal">

            <h2 class="titulo-formulario">✏️ Editar Producto</h2>


            <!-- =================================
                 MENSAJES DE ERROR
            ================================= -->

            <?php if (!empty($errores)) { ?>

                <div class="mensaje-error">

                    <strong>
                        🛑 No se puede actualizar el producto.
                    </strong>

                    <ul>

                        <?php foreach ($errores as $error) { ?>

                            <li>

                                <?php

                                echo htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                                ?>

                            </li>

                        <?php } ?>

                    </ul>

                </div>

            <?php } ?>

            <form method="POST"enctype="multipart/form-data">

                <label>Nombre del producto</label>

                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    required
                    maxlength="150"
                    value="<?php
                        echo htmlspecialchars(
                            $nombre,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>"
                >


                <div
                    id="contador-nombre"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 150 caracteres
                </div>

                <label>Código del producto</label>

                <input
                    type="text"
                    name="codigo_producto"
                    id="codigo_producto"
                    required
                    maxlength="50"
                    value="<?php
                        echo htmlspecialchars(
                            $codigo_producto,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>"
                >

                <div 
                    id="contador-codigo"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 50 caracteres
                </div>

                <label>Categoría</label>

                <select name="id_categoria"required>

                    <option value="">Seleccione una categoría</option>


                    <?php
                    while (
                        $categoria =
                        $resultadoCategorias->fetch_assoc()
                    ) {
                    ?>

                        <option
                            value="<?php
                                echo (int)
                                $categoria["id_categoria"];
                            ?>"
                            <?php
                            if (
                                (string)
                                $id_categoria ===
                                (string)
                                $categoria["id_categoria"]
                            ) {
                                echo "selected";
                            }
                            ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $categoria["nombre"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </option>

                    <?php } ?>

                </select>

                <label>Marca</label>

                <select name="id_marca"required>

                    <option value="">Seleccione una marca</option>


                    <?php
                    while (
                        $marca =
                        $resultadoMarcas->fetch_assoc()
                    ) {
                    ?>

                        <option
                            value="<?php
                                echo (int)
                                $marca["id_marca"];
                            ?>"
                            <?php
                            if (
                                (string)
                                $id_marca ===
                                (string)
                                $marca["id_marca"]
                            ) {
                                echo "selected";
                            }
                            ?>
                        >

                            <?php
                            echo htmlspecialchars(
                                $marca["nombre"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </option>

                    <?php } ?>

                </select>
                <!-- =================================
                     PRECIO Y PESO
                ================================= -->

                <div class="fila">

                    <div class="campo">

                        <label>Precio de venta</label>

                        <input
                            type="number"
                            name="precio"
                            id="precio"
                            step="0.01"
                            min="0"
                            max="99999999.99"
                            required
                            value="<?php
                                echo htmlspecialchars(
                                    $precio,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                            ?>"
                        >

                    </div>

                    <div class="campo">

                        <label>Peso (kg)</label>

                        <input
                            type="number"
                            name="peso"
                            id="peso"
                            step="0.01"
                            min="0"
                            max="999999.99"
                            value="<?php
                                echo htmlspecialchars(
                                    $peso ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                            ?>"
                        >

                    </div>

                </div>
                <!-- =================================
                     DESCRIPCIÓN
                ================================= -->

                <label>
                    Descripción
                </label>


                <textarea
                    name="descripcion"
                    id="descripcion"
                    rows="5"
                    maxlength="300"
                ><?php
                    echo htmlspecialchars(
                        $descripcion,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?></textarea>

                <div
                    id="contador-descripcion"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 300 caracteres
                </div>

                <!-- =================================
                     IMAGEN ACTUAL
                ================================= -->

                <label>
                    Imagen actual
                </label>


                <?php if ($imagen) { ?>

                    <img
                        src="../../<?php
                            echo htmlspecialchars(
                                $imagen["ruta_imagen"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                        ?>"
                        alt="Imagen del producto"
                        width="160"
                    >

                <?php } else { ?>

                    <p>Este producto no tiene imagen.</p>

                <?php } ?>

                <br><br>

                <!-- =================================
                     CAMBIAR IMAGEN
                ================================= -->

                <label>Cambiar imagen</label>


                <input
                    type="file"
                    name="imagen"
                    id="imagen"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >


                <small>
                    Formatos permitidos: JPG, JPEG, PNG y WEBP.
                    Máximo 2 MB.
                </small>

                <!-- =================================
                     ESTADO
                ================================= -->

                <label>Estado</label>

                <select name="estado">

                    <option
                        value="1"
                        <?php
                        if ($estado === "1") {
                            echo "selected";
                        }
                        ?>
                    >
                        Activo
                    </option>

                    <option
                        value="0"
                        <?php
                        if ($estado === "0") {
                            echo "selected";
                        }
                        ?>
                    >
                        Inactivo
                    </option>

                </select>


                <!-- =================================
                     DESTACADO
                ================================= -->

                <label>Producto destacado</label>

                <select name="destacado">

                    <option
                        value="0"
                        <?php
                        if ($destacado === "0") {
                            echo "selected";
                        }
                        ?>
                    >
                        No
                    </option>


                    <option
                        value="1"
                        <?php
                        if ($destacado === "1") {
                            echo "selected";
                        }
                        ?>
                    >
                        Sí
                    </option>

                </select>

                <br><br>

                <button type="submit"
                    class="btn-nuevo">💾 Actualizar Producto</button>


                <a href="productos.php"
                    class="btn-cancelar">Cancelar</a>

            </form>
        </div>
    </main>
</div>

<script src="../dashboard/dashboard.js"></script>


<script>

// =================================
// ELEMENTOS
// =================================

const nombre =
    document.getElementById("nombre");

const contadorNombre =
    document.getElementById("contador-nombre");


const codigo =
    document.getElementById("codigo_producto");

const contadorCodigo =
    document.getElementById("contador-codigo");


const descripcion =
    document.getElementById("descripcion");

const contadorDescripcion =
    document.getElementById("contador-descripcion");


const formulario =
    document.querySelector("form");


// =================================
// ACTUALIZAR CONTADOR
// =================================

function actualizarContador(
    campo,
    contador,
    maximo
) {

    // Primera barrera:
    // impedir superar el máximo.

    if (campo.value.length > maximo) {

        campo.value =
            campo.value.substring(0, maximo);

    }


    const cantidad =
        campo.value.length;


    contador.textContent =
        cantidad + " / " + maximo + " caracteres";


    // Cambiar apariencia al llegar al límite.

    if (cantidad >= maximo) {

        contador.style.color = "#c62828";

        contador.style.fontWeight = "bold";

    } else {

        contador.style.color = "#666";

        contador.style.fontWeight = "normal";

    }

}

// =================================
// EVENTOS
// =================================

nombre.addEventListener(
    "input",
    function () {

        actualizarContador(
            nombre,
            contadorNombre,
            150
        );

    }
);

codigo.addEventListener(
    "input",
    function () {

        actualizarContador(
            codigo,
            contadorCodigo,
            50
        );

    }
);

descripcion.addEventListener(
    "input",
    function () {

        actualizarContador(
            descripcion,
            contadorDescripcion,
            300
        );

    }
);

// =================================
// ACTUALIZAR AL CARGAR
// =================================

actualizarContador(
    nombre,
    contadorNombre,
    150
);


actualizarContador(
    codigo,
    contadorCodigo,
    50
);


actualizarContador(
    descripcion,
    contadorDescripcion,
    300
);

// =================================
// VALIDAR ANTES DE ENVIAR
// =================================

formulario.addEventListener(
    "submit",
    function (event) {

        // =============================
        // NOMBRE
        // =============================

        if (nombre.value.length > 150) {

            event.preventDefault();

            alert(
                "🛑 El nombre del producto no puede superar los 150 caracteres."
            );

            nombre.focus();

            return;

        }

        // =============================
        // CÓDIGO
        // =============================

        if (codigo.value.length > 50) {

            event.preventDefault();

            alert(
                "🛑 El código del producto no puede superar los 50 caracteres."
            );

            codigo.focus();

            return;

        }

        // =============================
        // DESCRIPCIÓN
        // =============================

        if (descripcion.value.length > 300) {

            event.preventDefault();

            alert(
                "🛑 La descripción no puede superar los 300 caracteres."
            );

            descripcion.focus();

            return;
        }
    }
);

</script>
</body>
</html>