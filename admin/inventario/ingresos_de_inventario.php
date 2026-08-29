<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $errores = [];

    // ================================
    // RECIBIR DATOS
    // ================================

    $id_proveedor = filter_input(INPUT_POST, "id_proveedor", FILTER_VALIDATE_INT);
    $fecha = trim($_POST["fecha"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $referencia = trim($_POST["referencia"] ?? "");
    $id_producto = filter_input(INPUT_POST, "id_producto", FILTER_VALIDATE_INT);
    $cantidad = trim($_POST["cantidad"] ?? "");
    $precio_compra = trim($_POST["precio_compra"] ?? "");
    $observacion = trim($_POST["observacion"] ?? "");

    $id_usuario = (int)$_SESSION["id_usuario"];


    // ================================
    // VALIDAR PROVEEDOR
    // ================================

    if ($id_proveedor === false || $id_proveedor === null || $id_proveedor <= 0) {

        $errores[] = "Debes seleccionar un proveedor.";

    } else {

        $sqlProveedor = "SELECT id_proveedor
                         FROM proveedores
                         WHERE id_proveedor = ?
                         AND estado = 1";

        $stmtProveedor = $conexion->prepare($sqlProveedor);
        $stmtProveedor->bind_param("i", $id_proveedor);
        $stmtProveedor->execute();

        $resultadoProveedor = $stmtProveedor->get_result();

        if ($resultadoProveedor->num_rows === 0) {

            $errores[] = "El proveedor seleccionado no es válido.";

        }

    }


    // ================================
    // VALIDAR FECHA
    // ================================

    if ($fecha === "") {

        $errores[] = "La fecha es obligatoria.";

    } else {

        $fechaValida = DateTime::createFromFormat("Y-m-d", $fecha);

        if (
            !$fechaValida ||
            $fechaValida->format("Y-m-d") !== $fecha
        ) {

            $errores[] = "La fecha ingresada no es válida.";

        }

    }


    // ================================
    // VALIDAR DOCUMENTO
    // ================================

    if (mb_strlen($documento) > 100) {

        $errores[] = "El documento no puede superar los 100 caracteres.";

    }

    if (preg_match('/[\x00-\x1F\x7F]/', $documento)) {

        $errores[] = "El documento contiene caracteres no permitidos.";

    }


    // ================================
    // VALIDAR REFERENCIA
    // ================================

    if (mb_strlen($referencia) > 100) {

        $errores[] = "La referencia no puede superar los 100 caracteres.";

    }

    if (preg_match('/[\x00-\x1F\x7F]/', $referencia)) {

        $errores[] = "La referencia contiene caracteres no permitidos.";

    }


    // ================================
    // VALIDAR PRODUCTO
    // ================================

    if ($id_producto === false || $id_producto === null || $id_producto <= 0) {

        $errores[] = "Debes seleccionar un producto.";

    } else {

        $sqlProducto = "SELECT id_producto
                        FROM productos
                        WHERE id_producto = ?
                        AND estado = 1";

        $stmtProducto = $conexion->prepare($sqlProducto);
        $stmtProducto->bind_param("i", $id_producto);
        $stmtProducto->execute();

        $resultadoProducto = $stmtProducto->get_result();

        if ($resultadoProducto->num_rows === 0) {

            $errores[] = "El producto seleccionado no es válido.";

        }

    }


    // ================================
    // VALIDAR CANTIDAD
    // ================================

    if (
        $cantidad === "" ||
        !ctype_digit($cantidad) ||
        (int)$cantidad <= 0
    ) {

        $errores[] = "La cantidad debe ser un número entero mayor que cero.";

    } elseif ((int)$cantidad > 1000000) {

        $errores[] = "La cantidad no puede superar 1.000.000 unidades.";


    } else {

        $cantidad = (int)$cantidad;

    }


    // ================================
    // VALIDAR PRECIO DE COMPRA
    // ================================

    if ($precio_compra === "") {

        $errores[] = "El precio de compra es obligatorio.";

    } elseif (!is_numeric($precio_compra)) {

        $errores[] = "El precio de compra debe ser un número válido.";

    } elseif ((float)$precio_compra <= 0) {

        $errores[] = "El precio de compra debe ser mayor que cero.";

    } elseif ((float)$precio_compra > 999999999.99) {

        $errores[] = "El precio de compra supera el límite permitido.";

    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $precio_compra)) {

        $errores[] = "El precio de compra puede tener máximo 2 decimales.";

    } else {

        $precio_compra = (float)$precio_compra;

    }


    // ================================
    // VALIDAR OBSERVACIÓN
    // ================================

    if (mb_strlen($observacion) > 500) {

        $errores[] = "La observación no puede superar los 500 caracteres.";

    }

    if (preg_match('/[\x00-\x1F\x7F]/', $observacion)) {

        $errores[] = "La observación contiene caracteres no permitidos.";

    }


    // ================================
    // SI HAY ERRORES, NO GUARDAR
    // ================================

    if (empty($errores)) {

        try {

            $conexion->begin_transaction();


            // ================================
            // CALCULAR TOTAL
            // ================================

            $total_compra = $cantidad * $precio_compra;


            // ================================
            // CREAR INGRESO
            // ================================

            $sqlIngreso = "INSERT INTO ingresos_inventario
            (
                id_proveedor,
                fecha,
                documento,
                referencia,
                total_compra,
                id_usuario,
                estado
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, 'Recibido'
            )";

            $stmtIngreso = $conexion->prepare($sqlIngreso);

            $stmtIngreso->bind_param(
                "isssdi",
                $id_proveedor,
                $fecha,
                $documento,
                $referencia,
                $total_compra,
                $id_usuario
            );

            $stmtIngreso->execute();

            $id_ingreso = $conexion->insert_id;


            // ================================
            // DETALLE DEL INGRESO
            // ================================

            $subtotal = $cantidad * $precio_compra;

            $sqlDetalle = "INSERT INTO detalle_ingreso
            (
                id_ingreso,
                id_producto,
                cantidad,
                precio_compra,
                subtotal
            )
            VALUES
            (
                ?, ?, ?, ?, ?
            )";

            $stmtDetalle = $conexion->prepare($sqlDetalle);

            $stmtDetalle->bind_param(
                "iiidd",
                $id_ingreso,
                $id_producto,
                $cantidad,
                $precio_compra,
                $subtotal
            );

            $stmtDetalle->execute();


            // ================================
            // ACTUALIZAR STOCK
            // ================================

            $sqlStock = "UPDATE inventario
                         SET stock_actual = stock_actual + ?
                         WHERE id_producto = ?";

            $stmtStock = $conexion->prepare($sqlStock);

            $stmtStock->bind_param(
                "ii",
                $cantidad,
                $id_producto
            );

            $stmtStock->execute();


            if ($stmtStock->affected_rows === 0) {

                throw new Exception(
                    "No se pudo actualizar el inventario del producto."
                );

            }


            // ================================
            // REGISTRAR MOVIMIENTO
            // ================================

            $sqlMovimiento = "INSERT INTO movimientos_inventario
            (
                id_producto,
                tipo,
                cantidad,
                motivo,
                observacion,
                id_proveedor,
                id_usuario
            )
            VALUES
            (
                ?, 'Entrada', ?, ?, ?, ?, ?
            )";

            $motivo = "Ingreso de inventario";

            $stmtMovimiento = $conexion->prepare($sqlMovimiento);

            $stmtMovimiento->bind_param(
                "iissii",
                $id_producto,
                $cantidad,
                $motivo,
                $observacion,
                $id_proveedor,
                $id_usuario
            );

            if (!$stmtMovimiento->execute()) {

                throw new Exception(
                    "No se pudo registrar el movimiento."
                );
            }
            
            // ================================
            // CONFIRMAR TODO
            // ================================

            $conexion->commit();

            header("Location: inventario.php");
            exit();


        } catch (Exception $e) {

            $conexion->rollback();

            $errores[] = "No se pudo registrar el ingreso. No se realizaron cambios en el inventario.";

        }

    }

}
// Obtener proveedores

$sqlProveedores = "SELECT
                    id_proveedor,
                    nombre
                   FROM proveedores
                   WHERE estado = 1
                   ORDER BY nombre ASC";

$resultadoProveedores = $conexion->query($sqlProveedores);

// Obtener productos

$sqlProductos = "SELECT
                    id_producto,
                    nombre,
                    codigo_producto
                 FROM productos
                 WHERE estado = 1
                 ORDER BY nombre ASC";

$resultadoProductos = $conexion->query($sqlProductos);

$busqueda = $_GET["busqueda"] ?? "";

$sql = "SELECT
            i.id_inventario,
            i.id_producto,
            i.stock_actual,
            i.stock_minimo,
            i.fecha_actualizacion,

            p.nombre,
            p.codigo_producto,

            c.nombre AS categoria,
            m.nombre AS marca,

            img.ruta_imagen

        FROM inventario i

        INNER JOIN productos p
        ON i.id_producto = p.id_producto

        INNER JOIN categorias c
        ON p.id_categoria = c.id_categoria

        LEFT JOIN marcas m
        ON p.id_marca = m.id_marca

        LEFT JOIN imagenes_producto img
        ON p.id_producto = img.id_producto
        AND img.principal = 1";

if (!empty($busqueda)) {

    $sql .= " WHERE
                p.nombre LIKE ?
                OR p.codigo_producto LIKE ?
                OR c.nombre LIKE ?
                OR m.nombre LIKE ?";

}

$sql .= " ORDER BY i.id_inventario DESC";

$stmt = $conexion->prepare($sql);

if (!empty($busqueda)) {

    $buscar = "%".$busqueda."%";

    $stmt->bind_param(
        "ssss",
        $buscar,
        $buscar,
        $buscar,
        $buscar
    );

}

$stmt->execute();

$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inventario | AGRANDA</title>

    <link rel="stylesheet" href="inventario.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- Menú lateral -->
    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Inventario</p>

        </div>

        <nav>

            <ul>

                <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                <li><a href="inventario.php" class="activo">📁 Inventario</a></li>

                <li><a href="../pedidos/pedidos.php">🛒 Pedidos</a></li>

                <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>

    <!-- Contenido -->
    <main class="contenido">

        <!-- Encabezado -->
        <header class="encabezado">

            <div class="titulo-panel">

                <h2>¡Bienvenido, <?php echo $_SESSION["nombre"]; ?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>

            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>
                    <span id="hora"></span>

                </div>

                <div class="usuario">

                    👤 <?php echo $_SESSION["nombre"]; ?>

                </div>

                <a href="../cerrar_sesion.php" class="btn-salir">Cerrar sesión</a>

            </div>

        </header>

        <!-- Área principal -->
        <section class="resumen">

            <h2>📥 Ingresos de Inventario</h2>

            <p>
                Desde aquí podrás registrar la entrada de mercancía al inventario.
            </p>

            <div class="principal">

                <form method="POST">

                    <select name="id_proveedor" required>

                        <option value="">
                            Seleccione un proveedor
                        </option>

                        <?php while($proveedor = $resultadoProveedores->fetch_assoc()){ ?>

                            <option value="<?php echo $proveedor["id_proveedor"]; ?>">

                                <?php echo $proveedor["nombre"]; ?>

                            </option>

                        <?php } ?>

                    </select>

                    <label>Fecha</label>

                    <input
                        type="date"
                        name="fecha"
                        required>

                    <label>Documento</label>

                    <input
                        type="text"
                        name="documento"
                        placeholder="Factura o documento"
                        maxlength="100">

                    <label>Referencia</label>

                    <input
                        type="text"
                        name="referencia"
                        placeholder="Referencia del ingreso"
                        maxlength="100">

                    <label>Producto</label>

                    <select name="id_producto" required>

                        <option value="">
                            Seleccione un producto
                        </option>

                        <?php while($producto = $resultadoProductos->fetch_assoc()){ ?>

                            <option value="<?php echo $producto["id_producto"]; ?>">

                                <?php echo $producto["nombre"]; ?>
                                (<?php echo $producto["codigo_producto"]; ?>)

                            </option>

                        <?php } ?>

                    </select>

                    <label>Cantidad</label>

                    <input
                        type="number"
                        name="cantidad"
                        min="1"
                        step="1"
                        required>

                    <label>Precio de compra</label>

                    <input
                        type="number"
                        name="precio_compra"
                        step="0.01"
                        min="0"
                        required>

                    <label>Observación</label>

                    <textarea
                        name="observacion"
                        id="observacion"
                        rows="4"
                        maxlength="500"><?php echo htmlspecialchars($observacion ?? "", ENT_QUOTES, "UTF-8"); ?></textarea>

                    <div class="contador-caracteres">
                        <span id="contador-observacion">0</span> / 500
                    </div>

                    <br><br>

                    <button
                        type="submit"
                        class="btn-nuevo">
                        💾 Registrar ingreso
                    </button>

                    <a
                        href="inventario.php"
                        class="btn-cancelar">
                        Cancelar
                    </a>

                </form>

            </div>

        </section>

<script src="../dashboard/dashboard.js"></script>

<script>

const observacion = document.getElementById("observacion");
const contadorObservacion = document.getElementById("contador-observacion");

function actualizarContador() {

    const longitud = observacion.value.length;

    contadorObservacion.textContent = longitud;

    if (longitud >= 500) {

        contadorObservacion.classList.add("limite");

    } else {

        contadorObservacion.classList.remove("limite");

    }

}

observacion.addEventListener("input", actualizarContador);
actualizarContador();

</script>
</body>
</html>