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
// VERIFICAR MÉTODO POST
// =================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: productos.php");
    exit();

}

// =================================
// VERIFICAR ACCIÓN
// =================================

if (!isset($_POST["accion"]) || $_POST["accion"] !== "agregar") {

    header("Location: productos.php");
    exit();

}

// =================================
// RECIBIR DATOS
// =================================

$nombre = trim($_POST["nombre"] ?? "");
$codigo_producto = trim($_POST["codigo_producto"] ?? "");

$id_categoria = $_POST["id_categoria"] ?? "";
$id_marca = $_POST["id_marca"] ?? "";
$id_proveedor = $_POST["id_proveedor"] ?? "";

$codigo_proveedor = trim($_POST["codigo_proveedor"] ?? "");

$precio = trim($_POST["precio"] ?? "");
$precio_compra = trim($_POST["precio_compra"] ?? "");

$stockActual = $_POST["stock_actual"] ?? "";
$stockMinimo = $_POST["stock_minimo"] ?? "";

$peso = trim($_POST["peso"] ?? "");

$descripcion = trim($_POST["descripcion"] ?? "");

$estado = $_POST["estado"] ?? "";
$destacado = $_POST["destacado"] ?? "";

// Características del producto
$atributoIds = $_POST["atributo_id"] ?? [];
$atributoValores = $_POST["atributo_valor"] ?? [];

$errores = [];

/* =================================
   VALIDACIÓN DE CARACTERÍSTICAS
================================= */

if (!is_array($atributoIds)) {
    $atributoIds = [];
}

if (!is_array($atributoValores)) {
    $atributoValores = [];
}

if (count($atributoIds) !== count($atributoValores)) {
    $errores[] = "Los datos de las características no son válidos.";
}

if (empty($errores)) {

    $atributosRecibidos = [];

    foreach ($atributoIds as $indice => $atributoId) {

        $valor = trim($atributoValores[$indice] ?? "");

        // Si la fila está completamente vacía, se ignora.
        if ($atributoId === "" && $valor === "") {
            continue;
        }

        // El ID debe ser un entero positivo.
        if (
            filter_var(
                $atributoId,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            ) === false
        ) {
            $errores[] = "Una de las características seleccionadas no es válida.";
            continue;
        }

        // El valor es obligatorio.
        if ($valor === "") {
            $errores[] = "Todas las características seleccionadas deben tener un valor.";
            continue;
        }

        // Longitud máxima del valor.
        if (mb_strlen($valor) > 300) {
            $errores[] = "El valor de una característica no puede superar los 300 caracteres.";
            continue;
        }

        // Evitar repetir el mismo atributo.
        if (in_array((int) $atributoId, $atributosRecibidos, true)) {
            $errores[] = "No puedes agregar la misma característica más de una vez.";
            continue;
        }

        $atributosRecibidos[] = (int) $atributoId;
    }
}

if (empty($errores) && !empty($atributosRecibidos)) {

    $stmtAtributo = $conexion->prepare(
        "SELECT id_atributo
         FROM atributos_producto
         WHERE id_atributo = ?
         AND estado = TRUE"
    );

    if (!$stmtAtributo) {

        $errores[] = "No fue posible validar las características del producto.";

    } else {

        foreach ($atributosRecibidos as $idAtributo) {

            $stmtAtributo->bind_param("i", $idAtributo);
            $stmtAtributo->execute();

            $resultadoAtributo = $stmtAtributo->get_result();

            if ($resultadoAtributo->num_rows === 0) {

                $errores[] = "Una de las características seleccionadas no existe o está inactiva.";
                break;

            }
        }

        $stmtAtributo->close();
    }
}

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

// La columna es TEXT.
// No tiene un límite VARCHAR definido.
// Se valida que no sea excesivamente grande.

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
// VALIDAR PROVEEDOR
// =================================

if (!filter_var($id_proveedor, FILTER_VALIDATE_INT, [
    "options" => ["min_range" => 1]
])) {

    $errores[] = "El proveedor seleccionado no es válido.";

} else {

    $id_proveedor = (int) $id_proveedor;

}

// =================================
// VALIDAR CÓDIGO DEL PROVEEDOR
// =================================

if ($codigo_proveedor !== "" && mb_strlen($codigo_proveedor) > 50) {

    $errores[] = "El código del proveedor no puede superar los 50 caracteres.";

}

// =================================
// VALIDAR PRECIO DE VENTA
// =================================

if ($precio === "") {

    $errores[] = "El precio de venta es obligatorio.";

} elseif (!is_numeric($precio)) {

    $errores[] = "El precio de venta debe ser un número válido.";

} elseif ((float) $precio < 0) {

    $errores[] = "El precio de venta no puede ser negativo.";

} elseif (strlen(explode(".", $precio)[0]) > 8) {

    $errores[] = "El precio de venta es demasiado grande.";

} elseif (isset(explode(".", $precio)[1]) && strlen(explode(".", $precio)[1]) > 2) {

    $errores[] = "El precio de venta solo puede tener máximo 2 decimales.";

} else {

    $precio = (float) $precio;

}

// =================================
// VALIDAR PRECIO DE COMPRA
// =================================

if ($precio_compra === "") {

    $errores[] = "El precio de compra es obligatorio.";

} elseif (!is_numeric($precio_compra)) {

    $errores[] = "El precio de compra debe ser un número válido.";

} elseif ((float) $precio_compra < 0) {

    $errores[] = "El precio de compra no puede ser negativo.";

} elseif (strlen(explode(".", $precio_compra)[0]) > 8) {

    $errores[] = "El precio de compra es demasiado grande.";

} elseif (isset(explode(".", $precio_compra)[1]) && strlen(explode(".", $precio_compra)[1]) > 2) {

    $errores[] = "El precio de compra solo puede tener máximo 2 decimales.";

} else {

    $precio_compra = (float) $precio_compra;

}

// =================================
// VALIDAR STOCK ACTUAL
// =================================

if (
    filter_var($stockActual, FILTER_VALIDATE_INT) === false ||
    (int) $stockActual < 0
) {

    $errores[] = "El stock inicial debe ser un número entero igual o mayor que 0.";

} else {

    $stockActual = (int) $stockActual;

}

// =================================
// VALIDAR STOCK MÍNIMO
// =================================

if (
    filter_var($stockMinimo, FILTER_VALIDATE_INT) === false ||
    (int) $stockMinimo < 0
) {

    $errores[] = "El stock mínimo debe ser un número entero igual o mayor que 0.";

} else {

    $stockMinimo = (int) $stockMinimo;

}

// =================================
// VALIDAR PESO
// =================================

if ($peso !== "") {

    if (!is_numeric($peso)) {

        $errores[] = "El peso debe ser un número válido.";

    } elseif ((float) $peso < 0) {

        $errores[] = "El peso no puede ser negativo.";

    } elseif (strlen(explode(".", $peso)[0]) > 6) {

        $errores[] = "El peso es demasiado grande.";

    } elseif (
        isset(explode(".", $peso)[1]) &&
        strlen(explode(".", $peso)[1]) > 2
    ) {

        $errores[] = "El peso solo puede tener máximo 2 decimales.";

    } else {

        $peso = (float) $peso;

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
// VALIDAR CATEGORÍA EN LA BD
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
// VALIDAR MARCA EN LA BD
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
// VALIDAR PROVEEDOR EN LA BD
// =================================

if (empty($errores)) {

    $sqlProveedor = "
        SELECT id_proveedor
        FROM proveedores
        WHERE id_proveedor = ?
        AND estado = 1
        LIMIT 1
    ";

    $stmtProveedor = $conexion->prepare($sqlProveedor);

    if (!$stmtProveedor) {

        $errores[] = "No fue posible verificar el proveedor.";

    } else {

        $stmtProveedor->bind_param("i", $id_proveedor);
        $stmtProveedor->execute();

        $resultadoProveedor = $stmtProveedor->get_result();

        if ($resultadoProveedor->num_rows === 0) {

            $errores[] = "El proveedor seleccionado no existe o está inactivo.";

        }

        $stmtProveedor->close();

    }

}

// =================================
// VALIDAR CÓDIGO ÚNICO
// =================================

if (empty($errores)) {

    $sqlCodigo = "
        SELECT id_producto
        FROM productos
        WHERE codigo_producto = ?
        LIMIT 1
    ";

    $stmtCodigo = $conexion->prepare($sqlCodigo);

    if (!$stmtCodigo) {

        $errores[] = "No fue posible verificar el código del producto.";

    } else {

        $stmtCodigo->bind_param("s", $codigo_producto);
        $stmtCodigo->execute();

        $resultadoCodigo = $stmtCodigo->get_result();

        if ($resultadoCodigo->num_rows > 0) {

            $errores[] = "Ya existe un producto con ese código.";

        }

        $stmtCodigo->close();

    }

}

// =================================
// VALIDAR IMAGEN
// =================================

$archivoImagen = null;
$tipoMime = null;

if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {

        $errores[] = "Ocurrió un error al subir la imagen.";

    } else {

        $archivoImagen = $_FILES["imagen"];

        // Máximo 2 MB
        $tamanoMaximo = 2 * 1024 * 1024;

        if ($archivoImagen["size"] > $tamanoMaximo) {

            $errores[] = "La imagen no puede superar los 2 MB.";

        }

        // Comprobar MIME real
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
// SI HAY ERRORES → VOLVER AL FORMULARIO
// =================================

if (!empty($errores)) {

    // Guardar errores para mostrarlos en el formulario
    $_SESSION["errores_producto"] = $errores;

    // Guardar los datos escritos por el usuario
    $_SESSION["datos_producto"] = $_POST;

    // Volver al formulario
    header("Location: agregar_producto.php");
    exit();

}

// =================================
// PREPARAR IMAGEN
// =================================

$rutaDestino = null;
$rutaImagen = null;

if ($archivoImagen !== null) {

    $carpeta = "../../uploads/productos/";

    if (!is_dir($carpeta)) {

        if (!mkdir($carpeta, 0755, true)) {

            die("No fue posible crear la carpeta de imágenes.");

        }

    }

    $extension = match ($tipoMime) {

        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"

    };

    $nombreImagen = bin2hex(random_bytes(16)) . "." . $extension;

    $rutaDestino = $carpeta . $nombreImagen;

    $rutaImagen = "uploads/productos/" . $nombreImagen;

}

// =================================
// INICIAR TRANSACCIÓN
// =================================

$conexion->begin_transaction();

// Variable para saber si se llegó a mover una imagen
$imagenMovida = false;

try {

    // =================================
    // INSERTAR PRODUCTO
    // =================================

    $sql = "
        INSERT INTO productos
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
        (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            "No fue posible preparar el registro del producto."
        );

    }

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

        throw new Exception(
            "No fue posible guardar el producto."
        );

    }

    $stmt->close();

    // Obtener ID
    $id_producto = $conexion->insert_id;

    /* =================================
    GUARDAR CARACTERÍSTICAS
    ================================= */

    if (!empty($atributosRecibidos)) {

        $stmtProductoAtributo = $conexion->prepare(
            "INSERT INTO producto_atributo
            (id_producto, id_atributo, valor)
            VALUES (?, ?, ?)"
        );

        if (!$stmtProductoAtributo) {
            throw new Exception("No fue posible preparar el registro de características.");
        }

        foreach ($atributosRecibidos as $indice => $idAtributo) {

            $valor = trim($atributoValores[$indice] ?? "");

            // Las filas vacías ya fueron ignoradas durante la validación.
            if ($valor === "") {
                continue;
            }

            $stmtProductoAtributo->bind_param(
                "iis",
                $id_producto,
                $idAtributo,
                $valor
            );

            if (!$stmtProductoAtributo->execute()) {
                throw new Exception("No fue posible guardar las características del producto.");
            }
        }

        $stmtProductoAtributo->close();
    }

    // =================================
    // INSERTAR PROVEEDOR
    // =================================

    $sqlProveedor = "
        INSERT INTO proveedor_producto
        (
            id_proveedor,
            id_producto,
            precio_compra,
            codigo_proveedor
        )
        VALUES
        (?, ?, ?, ?)
    ";

    $stmtProveedor = $conexion->prepare($sqlProveedor);

    if (!$stmtProveedor) {

        throw new Exception(
            "No fue posible preparar el proveedor del producto."
        );

    }

    $stmtProveedor->bind_param(
        "iids",
        $id_proveedor,
        $id_producto,
        $precio_compra,
        $codigo_proveedor
    );

    if (!$stmtProveedor->execute()) {

        throw new Exception(
            "No fue posible guardar el proveedor del producto."
        );

    }

    $stmtProveedor->close();

    // =================================
    // CREAR INVENTARIO
    // =================================

    $sqlInventario = "
        INSERT INTO inventario
        (
            id_producto,
            stock_actual,
            stock_minimo
        )
        VALUES
        (?, ?, ?)
    ";

    $stmtInventario = $conexion->prepare($sqlInventario);

    if (!$stmtInventario) {

        throw new Exception(
            "No fue posible preparar el inventario."
        );

    }

    $stmtInventario->bind_param(
        "iii",
        $id_producto,
        $stockActual,
        $stockMinimo
    );

    if (!$stmtInventario->execute()) {

        throw new Exception(
            "No fue posible crear el inventario."
        );

    }

    $stmtInventario->close();

    // =================================
    // GUARDAR IMAGEN
    // =================================

    if ($archivoImagen !== null) {

        if (!move_uploaded_file(
            $archivoImagen["tmp_name"],
            $rutaDestino
        )) {

            throw new Exception(
                "No fue posible guardar la imagen."
            );

        }

        $imagenMovida = true;

        $sqlImagen = "
            INSERT INTO imagenes_producto
            (
                id_producto,
                ruta_imagen,
                principal
            )
            VALUES
            (?, ?, 1)
        ";

        $stmtImagen = $conexion->prepare($sqlImagen);

        if (!$stmtImagen) {

            throw new Exception(
                "No fue posible preparar la imagen."
            );

        }

        $stmtImagen->bind_param(
            "is",
            $id_producto,
            $rutaImagen
        );

        if (!$stmtImagen->execute()) {

            throw new Exception(
                "No fue posible guardar la imagen en la base de datos."
            );

        }

        $stmtImagen->close();

    }

    // =================================
    // CONFIRMAR TRANSACCIÓN
    // =================================

    $conexion->commit();

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

    // Si la imagen ya se había guardado físicamente,
    // eliminarla porque la BD hizo rollback.

    if (
        $imagenMovida &&
        $rutaDestino !== null &&
        file_exists($rutaDestino)
    ) {

        unlink($rutaDestino);

    }

    // =================================
    // MOSTRAR ERROR
    // =================================

    echo "<h2>No fue posible guardar el producto.</h2>";

    echo "<p>" .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        ) .
        "</p>";

    echo '<a href="agregar_producto.php">Volver</a>';

    exit();

}
?>