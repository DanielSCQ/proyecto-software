<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// BUSCADOR
// =================================

$busqueda = trim($_GET["busqueda"] ?? "");


// =================================
// CONSULTA DE CLIENTES
// =================================

$sql = "SELECT
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.correo,
            u.telefono,
            u.estado,
            u.fecha_registro,

            COUNT(p.id_pedido) AS cantidad_pedidos

        FROM usuarios u

        LEFT JOIN pedidos p
        ON u.id_usuario = p.id_usuario

        WHERE u.rol = 'cliente'";


if ($busqueda !== "") {

    $sql .= " AND (
                u.nombre LIKE ?
                OR u.apellido LIKE ?
                OR CONCAT(u.nombre, ' ', u.apellido) LIKE ?
                OR u.correo LIKE ?
                OR u.telefono LIKE ?
              )";
}


$sql .= " GROUP BY
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.correo,
            u.telefono,
            u.estado,
            u.fecha_registro

          ORDER BY u.id_usuario DESC";


// =================================
// PREPARAR CONSULTA
// =================================

$stmt = $conexion->prepare($sql);


if (!$stmt) {

    die("Error en la consulta: " . $conexion->error);

}


// =================================
// PARÁMETROS DEL BUSCADOR
// =================================

if ($busqueda !== "") {

    $buscar = "%" . $busqueda . "%";

    $stmt->bind_param(
        "sssss",
        $buscar,
        $buscar,
        $buscar,
        $buscar,
        $buscar
    );
}


// =================================
// EJECUTAR
// =================================

$stmt->execute();

$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Clientes | AGRANDA</title>

    <link rel="stylesheet" href="clientes.css">

</head>


<body>

<div class="contenedor-dashboard">


    <!-- ===============================
         MENÚ LATERAL
    ================================ -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Clientes</p>

        </div>


        <nav>

            <ul>

                <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                <li><a href="../inventario/inventario.php">📁 Inventario</a></li>

                <li><a href="../pedidos/pedidos.php">🛒 Pedidos</a></li>

                <li><a href="clientes.php" class="activo">👥 Clientes</a></li>

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>


    <!-- ===============================
         CONTENIDO PRINCIPAL
    ================================ -->

    <main class="contenido">


        <!-- ===============================
             ENCABEZADO
        ================================ -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>¡Bienvenido,<?php echo htmlspecialchars($_SESSION["nombre"]); ?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>


            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>

                <div class="usuario">👤<?php echo htmlspecialchars($_SESSION["nombre"]); ?></div>

                <a href="../cerrar_sesion.php" class="btn-salir">Cerrar sesión</a>

            </div>

        </header>


        <!-- ===============================
             CLIENTES
        ================================ -->

        <section class="contenido-clientes">

            <div class="encabezado-clientes">

                <div>

                    <h2>👥 Clientes</h2>

                    <p>Consulta y administra los clientes registrados en AGRANDA.</p>

                </div>

            </div>


            <!-- ===============================
                 BARRA DE BÚSQUEDA
            ================================ -->

            <div class="barra-clientes">


                <form method="GET" action="clientes.php">

                    <input
                        type="text"
                        name="busqueda"
                        class="buscar-cliente"
                        placeholder="🔎 Buscar cliente..."
                        value="<?php echo htmlspecialchars($busqueda); ?>"
                    >

                </form>


            </div>


            <!-- ===============================
                 TABLA DE CLIENTES
            ================================ -->

            <div class="contenedor-tabla">

                <table class="tabla-clientes">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Cliente</th>

                            <th>Correo</th>

                            <th>Teléfono</th>

                            <th>Pedidos</th>

                            <th>Fecha de registro</th>

                            <th>Estado</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if ($resultado->num_rows > 0): ?>


                        <?php while ($cliente = $resultado->fetch_assoc()): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <?php echo $cliente["id_usuario"]; ?>

                                </td>


                                <!-- CLIENTE -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $cliente["nombre"] . " " . $cliente["apellido"]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <!-- CORREO -->

                                <td>

                                    <?php
                                    echo htmlspecialchars($cliente["correo"]);
                                    ?>

                                </td>

                                <!-- TELÉFONO -->

                                <td>

                                    <?php

                                    echo !empty($cliente["telefono"])

                                        ? htmlspecialchars($cliente["telefono"])

                                        : "No registrado";

                                    ?>

                                </td>

                                <!-- PEDIDOS -->

                                <td>

                                    <?php echo $cliente["cantidad_pedidos"]; ?>

                                </td>

                                <!-- FECHA -->

                                <td>

                                    <?php

                                    echo date(
                                        "d/m/Y",
                                        strtotime($cliente["fecha_registro"])
                                    );

                                    ?>

                                </td>

                                <!-- ESTADO -->

                                <td>

                                    <?php if ($cliente["estado"]): ?>

                                        <span class="estado-cliente estado-activo">Activo</span>

                                    <?php else: ?>

                                        <span class="estado-cliente estado-inactivo">Inactivo</span>

                                    <?php endif; ?>

                                </td>

                                <!-- ACCIONES -->
                                <td>

                                    <div class="acciones-cliente">

                                        <a href="ver_clientes.php?id=<?php echo $cliente["id_usuario"]; ?>"
                                            class="btn-ver">👁 Ver</a>


                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8" class="sin-clientes">
                                No se encontraron clientes.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </section>

    </main>

</div>

<script src="../dashboard/dashboard.js"></script>
</body>
</html>