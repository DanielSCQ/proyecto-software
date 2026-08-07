<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once("../config/conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_proveedor = $_POST["id_proveedor"];
    $fecha = $_POST["fecha"];
    $documento = trim($_POST["documento"]);
    $referencia = trim($_POST["referencia"]);
    $id_producto = $_POST["id_producto"];
    $cantidad = $_POST["cantidad"];
    $precio_compra = $_POST["precio_compra"];
    $observacion = trim($_POST["observacion"]);

    $total_compra = $cantidad * $precio_compra;

    // Crear ingreso

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
        $_SESSION["id_usuario"]
    );

    $stmtIngreso->execute();

    $id_ingreso = $conexion->insert_id;

    // Guardar detalle del ingreso

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

    // Actualizar el stock

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

    // Registrar movimiento en el historial

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
        $_SESSION["id_usuario"]
    );

    $stmtMovimiento->execute();

    header("Location: inventario.php");
    exit();

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

                <li><a href="dashboard.php">📊 Dashboard</a></li>

                <li><a href="productos.php">📦 Productos</a></li>

                <li><a href="categorias.php">🗂️ Categorías</a></li>

                <li><a href="marcas.php">🏷️ Marcas</a></li>

                <li><a href="proveedores.php">🚚 Proveedores</a></li>

                <li><a href="inventario.php">📁 Inventario</a></li>

                <li><a href="pedidos.php">🛒 Pedidos</a></li>

                <li><a href="clientes.php">👥 Clientes</a></li>

                <li><a href="contactos.php">✉️ Contactos</a></li>

                <li><a href="promociones.php">🎁 Promociones</a></li>

                <li><a href="configuracion.php">⚙️ Configuración</a></li>

                <li><a href="cerrar_sesion.php">🚪 Cerrar sesión</a></li>

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

                <a href="cerrar_sesion.php" class="btn-salir">Cerrar sesión</a>

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
                        placeholder="Factura o documento">

                    <label>Referencia</label>

                    <input
                        type="text"
                        name="referencia"
                        placeholder="Referencia del ingreso">

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
                        rows="4"></textarea>
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

<script src="dashboard.js"></script>
</body>
</html>