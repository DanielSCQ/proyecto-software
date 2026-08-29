<?php

session_start();

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

// Verificar sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

// Verificar ID
if (!isset($_GET["id"]) || !ctype_digit($_GET["id"]) || (int)$_GET["id"] <= 0) {
    header("Location: inventario.php");
    exit();
}

$id_inventario = (int)$_GET["id"];

$errores = [];

// Actualizar inventario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /*
    =================================
        VALIDAR STOCK ACTUAL
    =================================
    */

    $stockActualTexto = trim($_POST["stock_actual"] ?? "");

    if ($stockActualTexto === "") {

        $errores[] = "El stock actual es obligatorio.";

    } elseif (!ctype_digit($stockActualTexto)) {

        $errores[] = "El stock actual debe ser un número entero.";

    } elseif ((int)$stockActualTexto > 1000000) {

        $errores[] = "El stock actual no puede superar 1.000.000 unidades.";

    }


    /*
    =================================
        VALIDAR STOCK MÍNIMO
    =================================
    */

    $stockMinimoTexto = trim($_POST["stock_minimo"] ?? "");

    if ($stockMinimoTexto === "") {

        $errores[] = "El stock mínimo es obligatorio.";

    } elseif (!ctype_digit($stockMinimoTexto)) {

        $errores[] = "El stock mínimo debe ser un número entero.";

    } elseif ((int)$stockMinimoTexto > 1000000) {

        $errores[] = "El stock mínimo no puede superar 1.000.000 unidades.";

    }


    /*
    =================================
        CONVERTIR A ENTEROS
    =================================
    */

    if (empty($errores)) {

        $stock_actual = (int)$stockActualTexto;
        $stock_minimo = (int)$stockMinimoTexto;


        /*
        =================================
            VALIDAR RELACIÓN ENTRE STOCKS
        =================================
        */

        if ($stock_minimo > $stock_actual) {

            $errores[] =
                "El stock mínimo no puede ser mayor que el stock actual.";

        }

    }


    /*
    =================================
        PROCESAR ACTUALIZACIÓN
    =================================
    */

    if (empty($errores)) {

        // Obtener el stock anterior y el producto
        $sqlAnterior = "SELECT
                            stock_actual,
                            id_producto
                        FROM inventario
                        WHERE id_inventario = ?";

        $stmtAnterior = $conexion->prepare($sqlAnterior);

        $stmtAnterior->bind_param(
            "i",
            $id_inventario
        );

        $stmtAnterior->execute();

        $resultadoAnterior = $stmtAnterior->get_result();

        $datos = $resultadoAnterior->fetch_assoc();


        // Verificar que el inventario exista
        if (!$datos) {

            $errores[] =
                "El registro de inventario no existe.";

        } else {

            $stockAnterior = (int)$datos["stock_actual"];
            $id_producto = (int)$datos["id_producto"];


            /*
            =================================
                ACTUALIZAR INVENTARIO
            =================================
            */

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


            if ($stmt->execute()) {

                /*
                =================================
                    CALCULAR DIFERENCIA
                =================================
                */

                $cantidad =
                    $stock_actual - $stockAnterior;


                /*
                =================================
                    REGISTRAR MOVIMIENTO
                =================================
                */

                if ($cantidad != 0) {

                    $tipo = "Ajuste";

                    $motivo =
                        "Actualización manual desde el panel";

                    $observacion = "";

                    $id_usuario =
                        (int)$_SESSION["id_usuario"];


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

                    $stmtMovimiento =
                        $conexion->prepare($sqlMovimiento);

                    $stmtMovimiento->bind_param(
                        "isissi",
                        $id_producto,
                        $tipo,
                        $cantidad,
                        $motivo,
                        $observacion,
                        $id_usuario
                    );

                    if (!$stmtMovimiento->execute()) {

                        $errores[] =
                            "El inventario se actualizó, pero no fue posible registrar el movimiento.";

                    }

                }


                /*
                =================================
                    REDIRECCIÓN
                =================================
                */

                if (empty($errores)) {

                    header("Location: inventario.php");
                    exit();

                }

            } else {

                $errores[] =
                    "No fue posible actualizar el inventario.";

            }
        }
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

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>

        <div class="principal">

            <form method="POST">

            <?php if (!empty($errores)) { ?>

                <div class="mensaje-error">

                    <?php foreach ($errores as $error) { ?>

                        <p>🛑 <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>

                    <?php } ?>

                </div>

            <?php } ?>

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
                            max="1000000"
                            value="<?php echo $inventario['stock_actual']; ?>"
                            required>

                    </div>

                    <div class="campo">

                        <label>Stock mínimo</label>

                        <input
                            type="number"
                            name="stock_minimo"
                            min="0"
                            max="1000000"
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

<script src="../dashboard/dashboard.js"></script>

</body>

</html>