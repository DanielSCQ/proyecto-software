<?php

session_start();

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

// Verificar sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once("../config/conexion.php");

// Verificar ID
if (!isset($_GET["id"])) {
    header("Location: inventario.php");
    exit();
}

$id_inventario = $_GET["id"];

// Actualizar inventario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $stock_actual = (int)$_POST["stock_actual"];
    $stock_minimo = (int)$_POST["stock_minimo"];

    // Obtener el stock anterior y el producto
    $sqlAnterior = "SELECT stock_actual, id_producto
                    FROM inventario
                    WHERE id_inventario = ?";

    $stmtAnterior = $conexion->prepare($sqlAnterior);
    $stmtAnterior->bind_param("i", $id_inventario);
    $stmtAnterior->execute();

    $datos = $stmtAnterior->get_result()->fetch_assoc();

    $stockAnterior = $datos["stock_actual"];
    $id_producto = $datos["id_producto"];

    // Actualizar inventario
    $sql = "UPDATE inventario
            SET
                stock_actual = ?,
                stock_minimo = ?
            WHERE id_inventario = ?";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "iii",
        $stock_actual,
        $stock_minimo,
        $id_inventario
    );

    if($stmt->execute()){

        // Calcular diferencia
        $cantidad = $stock_actual - $stockAnterior;

        // Guardar movimiento solamente si hubo cambio
        if($cantidad != 0){

            $tipo = "Ajuste";
            $motivo = "Actualización manual desde el panel";
            $observacion = "";
            $id_usuario = $_SESSION["id_usuario"];

            $sqlMovimiento = "INSERT INTO movimientos_inventario
            (
                id_producto,
                tipo,
                cantidad,
                motivo,
                observacion,
                id_usuario
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?
            )";

            $stmtMovimiento = $conexion->prepare($sqlMovimiento);

            $stmtMovimiento->bind_param(
                "isissi",
                $id_producto,
                $tipo,
                $cantidad,
                $motivo,
                $observacion,
                $id_usuario
            );

            $stmtMovimiento->execute();

        }

        header("Location: inventario.php");
        exit();

    }else{

        echo "Error al actualizar el inventario.";

    }

}

// Obtener datos del inventario

$sql = "SELECT
            i.*,
            p.nombre
        FROM inventario i

        INNER JOIN productos p
        ON i.id_producto = p.id_producto

        WHERE i.id_inventario = ?";

$stmt = $conexion->prepare($sql);

$stmt->bind_param("i",$id_inventario);

$stmt->execute();

$resultado = $stmt->get_result();

$inventario = $resultado->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Editar Inventario | AGRANDA</title>

<link rel="stylesheet" href="inventario.css">

</head>

<body>

<div class="contenedor-dashboard">

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

    <main class="contenido">

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

                <a href="cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>

        <div class="principal">

            <form method="POST">

                <h2>✏️ Editar Inventario</h2>

                <label>Producto</label>

                <input
                    type="text"
                    value="<?php echo $inventario['nombre']; ?>"
                    readonly>

                <div class="fila">

                    <div class="campo">

                        <label>Stock actual</label>

                        <input
                            type="number"
                            name="stock_actual"
                            min="0"
                            value="<?php echo $inventario['stock_actual']; ?>"
                            required>

                    </div>

                    <div class="campo">

                        <label>Stock mínimo</label>

                        <input
                            type="number"
                            name="stock_minimo"
                            min="0"
                            value="<?php echo $inventario['stock_minimo']; ?>"
                            required>

                    </div>

                </div>

                <br><br>

                <button
                    type="submit"
                    class="btn-nuevo">

                    💾 Actualizar Inventario

                </button>

                <a
                    href="inventario.php"
                    class="btn-cancelar">

                    Cancelar

                </a>

            </form>

        </div>

    </main>

</div>

<script src="dashboard.js"></script>

</body>

</html>