<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

/* ==============================
   FILTROS
================================= */

$busqueda = trim($_GET["busqueda"] ?? "");
$estado = $_GET["estado"] ?? "todos";


/* ==============================
   CONSULTA DE PEDIDOS
================================= */

$sql = "SELECT
            p.id_pedido,
            p.total,
            p.metodo_pago,
            p.fecha_pedido,
            p.fecha_actualizacion,

            u.nombre,
            u.apellido,
            u.correo,

            ep.id_estado,
            ep.nombre AS estado,
            ep.descripcion AS descripcion_estado

        FROM pedidos p

        INNER JOIN usuarios u
            ON p.id_usuario = u.id_usuario

        INNER JOIN estado_pedido ep
            ON p.id_estado = ep.id_estado

        WHERE 1=1";


/* ==============================
   FILTRO POR ESTADO
================================= */

if ($estado !== "todos" && is_numeric($estado)) {

    $sql .= " AND ep.id_estado = ?";

}


/* ==============================
   BUSCADOR
================================= */

if ($busqueda !== "") {

    $sql .= " AND (
                CAST(p.id_pedido AS CHAR) LIKE ?
                OR u.nombre LIKE ?
                OR u.apellido LIKE ?
                OR u.correo LIKE ?
              )";

}


/* ==============================
   ORDEN
================================= */

$sql .= " ORDER BY p.fecha_pedido DESC";


$stmt = $conexion->prepare($sql);


/* ==============================
   PARÁMETROS
================================= */

$tipos = "";
$parametros = [];


if ($estado !== "todos" && is_numeric($estado)) {

    $tipos .= "i";
    $parametros[] = $estado;

}


if ($busqueda !== "") {

    $buscar = "%" . $busqueda . "%";

    $tipos .= "ssss";

    $parametros[] = $buscar;
    $parametros[] = $buscar;
    $parametros[] = $buscar;
    $parametros[] = $buscar;

}


if (!empty($parametros)) {

    $stmt->bind_param($tipos, ...$parametros);

}


$stmt->execute();

$resultado = $stmt->get_result();


/* ==============================
   CONTADOR
================================= */

$total_pedidos = $resultado->num_rows;

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pedidos | AGRANDA</title>

    <link rel="stylesheet" href="pedidos.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- Menú lateral -->
    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Pedidos</p>

        </div>

        <nav>

            <ul>

                <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                <li><a href="../inventario/inventario.php">📁 Inventario</a></li>

                <li><a href="pedidos.php" class="activo">🛒 Pedidos</a></li>

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

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>

        <!-- Área principal -->
        <section class="resumen">

            <div class="titulo-seccion">

                <h2>🛒 Gestor de Pedidos</h2>

                <p>
                    Administra y realiza seguimiento a los pedidos realizados en AGRANDA.
                </p>

            </div>


            <!-- CONTADOR -->

            <div class="contador-pedidos">

                <strong>
                    <?php echo $total_pedidos; ?>
                </strong>

                <?php echo ($total_pedidos == 1) ? "pedido" : "pedidos"; ?>

            </div>


            <!-- FILTROS -->

            <div class="filtros-pedidos">

                <a href="pedidos.php"
                class="<?php echo ($estado === 'todos') ? 'activo' : ''; ?>">

                    📋 Todos

                </a>


                <a href="pedidos.php?estado=1"
                class="<?php echo ($estado == 1) ? 'activo' : ''; ?>">

                    🕐 Pendientes

                </a>


                <a href="pedidos.php?estado=2"
                class="<?php echo ($estado == 2) ? 'activo' : ''; ?>">

                    ⚙️ En proceso

                </a>


                <a href="pedidos.php?estado=3"
                class="<?php echo ($estado == 3) ? 'activo' : ''; ?>">

                    🚚 Enviados

                </a>


                <a href="pedidos.php?estado=4"
                class="<?php echo ($estado == 4) ? 'activo' : ''; ?>">

                    ✅ Entregados

                </a>


                <a href="pedidos.php?estado=5"
                class="<?php echo ($estado == 5) ? 'activo' : ''; ?>">

                    ❌ Cancelados

                </a>

            </div>


            <!-- BUSCADOR -->

            <form method="GET" class="buscador-pedidos">

                <?php if ($estado !== "todos") { ?>

                    <input
                        type="hidden"
                        name="estado"
                        value="<?php echo htmlspecialchars($estado); ?>"
                    >

                <?php } ?>


                <input
                    type="text"
                    name="busqueda"
                    placeholder="Buscar por pedido, cliente o correo..."
                    value="<?php echo htmlspecialchars($busqueda); ?>"
                >


                <button type="submit">

                    🔍 Buscar

                </button>

            </form>


            <!-- TABLA -->

            <div class="tabla-contenedor">

                <table class="tabla-pedidos">

                    <thead>

                        <tr>

                            <th>Pedido</th>

                            <th>Cliente</th>

                            <th>Fecha</th>

                            <th>Total</th>

                            <th>Método de pago</th>

                            <th>Estado</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if ($resultado->num_rows > 0) { ?>


                            <?php while ($pedido = $resultado->fetch_assoc()) { ?>

                                <tr>

                                    <!-- PEDIDO -->

                                    <td>

                                        <strong>
                                            #<?php echo $pedido["id_pedido"]; ?>
                                        </strong>

                                    </td>


                                    <!-- CLIENTE -->

                                    <td>

                                        <strong>

                                            <?php

                                            echo htmlspecialchars(
                                                $pedido["nombre"] . " " . $pedido["apellido"]
                                            );

                                            ?>

                                        </strong>

                                        <br>

                                        <small>

                                            <?php echo htmlspecialchars($pedido["correo"]); ?>

                                        </small>

                                    </td>


                                    <!-- FECHA -->

                                    <td>

                                        <?php

                                        echo date(
                                            "d/m/Y H:i",
                                            strtotime($pedido["fecha_pedido"])
                                        );

                                        ?>

                                    </td>


                                    <!-- TOTAL -->

                                    <td>

                                        <strong>

                                            $<?php echo number_format(
                                                $pedido["total"],
                                                0,
                                                ",",
                                                "."
                                            ); ?>

                                        </strong>

                                    </td>


                                    <!-- MÉTODO -->

                                    <td>

                                        <?php echo htmlspecialchars(
                                            $pedido["metodo_pago"]
                                        ); ?>

                                    </td>


                                    <!-- ESTADO -->

                                    <td>

                                        <span class="estado estado-<?php echo $pedido["id_estado"]; ?>">

                                            <?php echo htmlspecialchars(
                                                $pedido["estado"]
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- ACCIONES -->

                                    <td>

                                        <a
                                            href="detalle_pedido.php?id=<?php echo $pedido["id_pedido"]; ?>"
                                            class="btn-ver-pedido"
                                        >
                                            👁️ Ver detalle
                                        </a>
                                    </td>

                                </tr>

                            <?php } ?>


                        <?php } else { ?>

                            <tr>

                                <td colspan="7" class="sin-pedidos">

                                    📦 No hay pedidos registrados.

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </section>
        
    </main>

</div>

<script src="../dashboard/dashboard.js"></script>
</body>
</html>