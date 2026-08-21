<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// BUSCADOR Y FILTRO
// =================================

$busqueda = trim($_GET["busqueda"] ?? "");
$estado = $_GET["estado"] ?? "";


// =================================
// CONSULTA DE CONTACTOS
// =================================

$sql = "SELECT
            id_contacto,
            id_usuario,
            nombre,
            correo,
            telefono,
            asunto,
            mensaje,
            fecha_envio,
            estado,
            respuesta
        FROM contactos
        WHERE 1=1";


// =================================
// BUSCADOR
// =================================

if ($busqueda !== "") {

    $sql .= " AND (
                nombre LIKE ?
                OR correo LIKE ?
                OR asunto LIKE ?
                OR telefono LIKE ?
              )";
}


// =================================
// FILTRO POR ESTADO
// =================================

if ($estado !== "") {

    $sql .= " AND estado = ?";
}


$sql .= " ORDER BY id_contacto DESC";


// =================================
// PREPARAR CONSULTA
// =================================

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error en la consulta: " . $conexion->error);
}


// =================================
// PARÁMETROS
// =================================

if ($busqueda !== "" && $estado !== "") {

    $buscar = "%" . $busqueda . "%";

    $stmt->bind_param(
        "sssss",
        $buscar,
        $buscar,
        $buscar,
        $buscar,
        $estado
    );

} elseif ($busqueda !== "") {

    $buscar = "%" . $busqueda . "%";

    $stmt->bind_param(
        "ssss",
        $buscar,
        $buscar,
        $buscar,
        $buscar
    );

} elseif ($estado !== "") {

    $stmt->bind_param(
        "s",
        $estado
    );
}


// =================================
// EJECUTAR
// =================================

$stmt->execute();

$resultado = $stmt->get_result();

// =================================
// ESTADÍSTICAS DE CONTACTOS
// =================================

$sql_estadisticas = "SELECT
    SUM(estado = 'pendiente') AS pendientes,
    SUM(estado = 'leido') AS leidos,
    SUM(estado = 'respondido') AS respondidos,
    SUM(estado = 'cerrado') AS cerrados
    FROM contactos";

$resultado_estadisticas = $conexion->query($sql_estadisticas);

$estadisticas = $resultado_estadisticas->fetch_assoc();

$pendientes = $estadisticas["pendientes"] ?? 0;
$leidos = $estadisticas["leidos"] ?? 0;
$respondidos = $estadisticas["respondidos"] ?? 0;
$cerrados = $estadisticas["cerrados"] ?? 0;

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contactos | AGRANDA</title>

    <link rel="stylesheet" href="contactos.css">

</head>


<body>

<div class="contenedor-dashboard">


    <!-- =================================
         MENÚ LATERAL
    ================================== -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Contactos</p>

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

                <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                <li><a href="contactos.php" class="activo">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>

    <!-- =================================
         CONTENIDO
    ================================== -->

    <main class="contenido">

        <!-- =================================
             ENCABEZADO
        ================================== -->

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

                <a href="../cerrar_sesion.php"
                    class="btn-salir">Cerrar sesión</a>

            </div>

        </header>

        <!-- =================================
             CONTENIDO CONTACTOS
        ================================== -->

        <section class="contenido-contactos">

            <div class="encabezado-contactos">

                <div>

                    <h2>✉️ Gestor de Contactos</h2>

                    <p>
                        Consulta, administra y responde los mensajes
                        recibidos desde AGRANDA.
                    </p>

                </div>

            </div>


            <!-- =================================
                 TARJETAS
            ================================== -->

            <div class="tarjetas-estadisticas">

                <div class="estadistica">
                    <div class="estadistica-icono">🟡</div>

                    <div>
                        <span class="estadistica-titulo">Pendientes</span>
                        <strong><?php echo $pendientes; ?></strong>
                    </div>
                </div>


                <div class="estadistica">
                    <div class="estadistica-icono">🔵</div>

                    <div>
                        <span class="estadistica-titulo">Leídos</span>
                        <strong><?php echo $leidos; ?></strong>
                    </div>
                </div>


                <div class="estadistica">
                    <div class="estadistica-icono">🟢</div>

                    <div>
                        <span class="estadistica-titulo">Respondidos</span>
                        <strong><?php echo $respondidos; ?></strong>
                    </div>
                </div>


                <div class="estadistica">
                    <div class="estadistica-icono">⚫</div>

                    <div>
                        <span class="estadistica-titulo">Cerrados</span>
                        <strong><?php echo $cerrados; ?></strong>
                    </div>
                </div>

            </div>
            <!-- =================================
                 BARRA DE HERRAMIENTAS
            ================================== -->

            <div class="barra-contactos">


                <form method="GET" action="contactos.php">

                    <input
                        type="search"
                        name="busqueda"
                        class="buscar-cliente"
                        placeholder="🔎 Buscar contacto..."
                        value="<?php echo htmlspecialchars($busqueda); ?>"
                    >

                    <button type="submit" class="btn-ver">
                        Buscar
                    </button>

                </form>

                <form method="GET" action="contactos.php">

                    <input
                        type="hidden"
                        name="busqueda"
                        value="<?php echo htmlspecialchars($busqueda); ?>"
                    >

                    <select name="estado" onchange="this.form.submit()">

                        <option value="">Todos los estados</option>

                        <option value="pendiente"
                            <?php echo $estado === "pendiente" ? "selected" : ""; ?>>
                            Pendientes
                        </option>

                        <option value="leido"
                            <?php echo $estado === "leido" ? "selected" : ""; ?>>
                            Leídos
                        </option>

                        <option value="respondido"
                            <?php echo $estado === "respondido" ? "selected" : ""; ?>>
                            Respondidos
                        </option>

                        <option value="cerrado"
                            <?php echo $estado === "cerrado" ? "selected" : ""; ?>>
                            Cerrados
                        </option>

                    </select>

                </form>

            </div>

            <!-- =================================
                 TABLA
            ================================== -->

            <div class="contenedor-tabla">

                <table class="tabla-contactos">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Nombre</th>

                            <th>Correo</th>

                            <th>Asunto</th>

                            <th>Fecha</th>

                            <th>Estado</th>

                            <th>Acción</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($resultado->num_rows > 0): ?>

                        <?php while ($contacto = $resultado->fetch_assoc()): ?>

                            <tr>

                                <!-- ID -->
                                <td>
                                    <?php echo $contacto["id_contacto"]; ?>
                                </td>


                                <!-- NOMBRE -->
                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($contacto["nombre"]); ?>
                                    </strong>
                                </td>


                                <!-- CORREO -->
                                <td>
                                    <?php echo htmlspecialchars($contacto["correo"]); ?>
                                </td>


                                <!-- ASUNTO -->
                                <td>
                                    <?php echo htmlspecialchars($contacto["asunto"]); ?>
                                </td>


                                <!-- FECHA -->
                                <td>
                                    <?php
                                    echo date(
                                        "d/m/Y H:i",
                                        strtotime($contacto["fecha_envio"])
                                    );
                                    ?>
                                </td>


                                <!-- ESTADO -->
                                <td>

                                    <?php

                                    switch ($contacto["estado"]) {

                                        case "pendiente":
                                            echo '<span class="estado-cliente estado-inactivo">
                                                    Pendiente
                                                </span>';
                                            break;

                                        case "leido":
                                            echo '<span class="estado-cliente">
                                                    Leído
                                                </span>';
                                            break;

                                        case "respondido":
                                            echo '<span class="estado-cliente estado-activo">
                                                    Respondido
                                                </span>';
                                            break;

                                        case "cerrado":
                                            echo '<span class="estado-cliente">
                                                    Cerrado
                                                </span>';
                                            break;
                                    }

                                    ?>

                                </td>


                                <!-- ACCIÓN -->
                                <td>

                                    <div class="acciones-contacto">

                                        <a
                                            href="ver_contacto.php?id=<?php echo $contacto["id_contacto"]; ?>"
                                            class="btn-ver"
                                        >
                                            👁 Ver
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>


                    <?php else: ?>

                        <tr>

                            <td colspan="7" class="sin-clientes">

                                No se encontraron contactos.

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