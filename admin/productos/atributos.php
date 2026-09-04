<?php

session_start();

// Verificar sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

$errores = [];

// =================================
// GUARDAR ATRIBUTO
// =================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $estado = $_POST["estado"] ?? "";

    // Validar nombre
    if ($nombre === "") {

        $errores[] = "El nombre de la característica es obligatorio.";

    } elseif (mb_strlen($nombre) > 100) {

        $errores[] = "El nombre de la característica no puede superar los 100 caracteres.";

    }

    // Validar estado
    if ($estado !== "0" && $estado !== "1") {

        $errores[] = "El estado seleccionado no es válido.";

    }

    // Verificar que no exista otro atributo con el mismo nombre
    if (empty($errores)) {

        $sql = "SELECT id_atributo
                FROM atributos_producto
                WHERE nombre = ?
                LIMIT 1";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {

            $errores[] = "No fue posible validar la característica.";

        } else {

            $stmt->bind_param("s", $nombre);
            $stmt->execute();

            $resultadoExistente = $stmt->get_result();

            if ($resultadoExistente->num_rows > 0) {

                $errores[] = "Ya existe una característica con ese nombre.";

            }

            $stmt->close();
        }
    }

    // Guardar atributo
    if (empty($errores)) {

        $sql = "INSERT INTO atributos_producto (nombre, estado)
                VALUES (?, ?)";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {

            $errores[] = "No fue posible preparar el registro de la característica.";

        } else {

            $estadoInt = (int) $estado;

            $stmt->bind_param(
                "si",
                $nombre,
                $estadoInt
            );

            if ($stmt->execute()) {

                header("Location: atributos.php");
                exit();

            } else {

                $errores[] = "No fue posible guardar la característica.";

            }

            $stmt->close();
        }
    }
}

// =================================
// OBTENER ATRIBUTOS
// =================================

$sql = "SELECT *
        FROM atributos_producto
        ORDER BY id_atributo DESC";

$resultado = $conexion->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Atributos de productos | AGRANDA</title>

    <link rel="stylesheet" href="productos.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- Menú lateral -->
    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Atributos de productos</p>

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

    <!-- Contenido principal -->
    <main class="contenido">

        <!-- Encabezado -->
        <header class="encabezado">

            <div class="titulo-panel">

                <h2>
                    ¡Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"], ENT_QUOTES, "UTF-8"); ?>!
                </h2>

                <p>
                    Panel de Administración AGRANDA
                </p>

            </div>

            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>
                    <span id="hora"></span>

                </div>

                <div class="usuario">

                    👤
                    <?php echo htmlspecialchars($_SESSION["nombre"], ENT_QUOTES, "UTF-8"); ?>

                </div>

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>

        <!-- Área de trabajo -->
        <section class="resumen pagina-productos">

            <h2>
                Características de productos
            </h2>

            <p>
                Desde aquí podrás administrar las características disponibles para los productos de AGRANDA.
            </p>

            <a href="productos.php" class="btn-nuevo">
                ← Volver a Productos
            </a>
            <br></br>

            <?php if (!empty($errores)) { ?>

                <div class="mensaje-error">

                    <strong>
                        No se puede guardar la característica.
                    </strong>

                    <ul>

                        <?php foreach ($errores as $error) { ?>

                            <li>
                                <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
                            </li>

                        <?php } ?>

                    </ul>

                </div>

            <?php } ?>

            <!-- FORMULARIO -->
            <form method="POST">

                <label for="nombre">
                    Nombre de la característica
                </label>

                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    maxlength="100"
                    required
                    value="<?php echo htmlspecialchars($_POST["nombre"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                >

                <div
                    id="contador-nombre"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;"
                >
                    0 / 100 caracteres
                </div>

                <label for="estado">
                    Estado
                </label>

                <select name="estado" id="estado">

                    <option value="1">
                        Activa
                    </option>

                    <option value="0">
                        Inactiva
                    </option>

                </select>

                <br><br>

                <button type="submit" class="btn-nuevo">
                    Guardar característica
                </button>

            </form>

            <hr><br>

            <!-- TABLA -->
            <table class="tabla-productos">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                    <?php while ($atributo = $resultado->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php echo (int) $atributo["id_atributo"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($atributo["nombre"], ENT_QUOTES, "UTF-8"); ?>
                            </td>

                            <td>

                                <?php if ($atributo["estado"]) { ?>

                                    <span class="estado-activo">
                                        Activa
                                    </span>

                                <?php } else { ?>

                                    <span class="estado-inactivo">
                                        Inactiva
                                    </span>

                                <?php } ?>

                            </td>

                            <td class="acciones">

                                <a
                                    href="editar_atributo.php?id=<?php echo (int) $atributo["id_atributo"]; ?>"
                                    class="btn-editar"
                                    title="Editar característica"
                                >
                                    ✏️
                                </a>

                                <a
                                    href="eliminar_atributo.php?id=<?php echo (int) $atributo["id_atributo"]; ?>"
                                    class="btn-eliminar"
                                    title="Eliminar característica"
                                    onclick="return confirmarEliminacion();"
                                >
                                    🗑️
                                </a>

                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </section>

    </main>

</div>

<script src="../dashboard/dashboard.js"></script>

<script>

const nombre = document.getElementById("nombre");
const contadorNombre = document.getElementById("contador-nombre");

function actualizarContadorNombre() {

    const cantidad = nombre.value.length;

    contadorNombre.textContent =
        cantidad + " / 100 caracteres";

    if (cantidad >= 100) {

        contadorNombre.style.color = "#c62828";
        contadorNombre.style.fontWeight = "bold";

    } else {

        contadorNombre.style.color = "#666";
        contadorNombre.style.fontWeight = "normal";

    }

}

nombre.addEventListener("input", actualizarContadorNombre);

actualizarContadorNombre();

nombre.addEventListener("input", actualizarContadorNombre);

actualizarContadorNombre();

function confirmarEliminacion() {

    return confirm(
        "¿Está seguro de eliminar esta característica?\n\n" +
        "También se eliminarán las relaciones de esta característica con los productos.\n\n" +
        "Esta acción no se puede deshacer."
    );

}

</script>

</body>
</html>