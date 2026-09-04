<?php

session_start();

// =================================
// VERIFICAR SESIÓN
// =================================

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

$errores = [];

// =================================
// VALIDAR ID DEL ATRIBUTO
// =================================

$idAtributo = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($idAtributo === false || $idAtributo === null || $idAtributo < 1) {
    header("Location: atributos.php");
    exit();
}

// =================================
// OBTENER ATRIBUTO
// =================================

$sql = "SELECT id_atributo, nombre, estado
        FROM atributos_producto
        WHERE id_atributo = ?
        LIMIT 1";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("No fue posible preparar la consulta.");
}

$stmt->bind_param("i", $idAtributo);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    $stmt->close();
    header("Location: atributos.php");
    exit();
}

$atributo = $resultado->fetch_assoc();

$stmt->close();

// =================================
// ACTUALIZAR ATRIBUTO
// =================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $estado = $_POST["estado"] ?? "";

    // =================================
    // VALIDAR NOMBRE
    // =================================

    if ($nombre === "") {

        $errores[] = "El nombre de la característica es obligatorio.";

    } elseif (mb_strlen($nombre) > 100) {

        $errores[] = "El nombre de la característica no puede superar los 100 caracteres.";

    }

    // =================================
    // VALIDAR ESTADO
    // =================================

    if ($estado !== "0" && $estado !== "1") {

        $errores[] = "El estado seleccionado no es válido.";

    }

    // =================================
    // VERIFICAR NOMBRE DUPLICADO
    // =================================

    if (empty($errores)) {

        $sql = "SELECT id_atributo
                FROM atributos_producto
                WHERE nombre = ?
                AND id_atributo <> ?
                LIMIT 1";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {

            $errores[] = "No fue posible validar la característica.";

        } else {

            $stmt->bind_param(
                "si",
                $nombre,
                $idAtributo
            );

            $stmt->execute();

            $resultadoExistente = $stmt->get_result();

            if ($resultadoExistente->num_rows > 0) {

                $errores[] = "Ya existe otra característica con ese nombre.";

            }

            $stmt->close();
        }
    }

    // =================================
    // ACTUALIZAR
    // =================================

    if (empty($errores)) {

        $sql = "UPDATE atributos_producto
                SET nombre = ?, estado = ?
                WHERE id_atributo = ?";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {

            $errores[] = "No fue posible preparar la actualización.";

        } else {

            $estadoInt = (int) $estado;

            $stmt->bind_param(
                "sii",
                $nombre,
                $estadoInt,
                $idAtributo
            );

            if ($stmt->execute()) {

                $stmt->close();

                header("Location: atributos.php");
                exit();

            } else {

                $errores[] = "No fue posible actualizar la característica.";

                $stmt->close();
            }
        }
    }

    // Mantener los valores enviados si ocurrió un error
    $atributo["nombre"] = $nombre;
    $atributo["estado"] = $estado;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar característica | AGRANDA</title>

    <link rel="stylesheet" href="productos.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- =================================
         MENÚ LATERAL
         ================================= -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Editar característica</p>

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


    <!-- =================================
         CONTENIDO PRINCIPAL
         ================================= -->

    <main class="contenido">

        <!-- ENCABEZADO -->

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

                    <?php
                    echo htmlspecialchars(
                        $_SESSION["nombre"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>

                </div>

                <a
                    href="../cerrar_sesion.php"
                    class="btn-salir"
                >
                    Cerrar sesión
                </a>

            </div>

        </header>


        <!-- =================================
             ÁREA DE TRABAJO
             ================================= -->

        <section class="resumen pagina-productos">

            <h2>
                Editar característica
            </h2>

            <p>
                Modifica la información de la característica seleccionada.
            </p>


            <!-- BOTÓN VOLVER -->

            <a
                href="atributos.php"
                class="btn-nuevo"
            >
                ← Volver a Características
            </a>

            <br><br>


            <!-- =================================
                 MENSAJES DE ERROR
                 ================================= -->

            <?php if (!empty($errores)) { ?>

                <div class="mensaje-error">

                    <strong>
                        No se puede actualizar la característica.
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


            <!-- =================================
                 FORMULARIO
                 ================================= -->

            <form
                method="POST"
                novalidate
            >

                <label for="nombre">
                    Nombre de la característica
                </label>

                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    maxlength="100"
                    required
                    value="<?php
                    echo htmlspecialchars(
                        $atributo["nombre"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>"
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

                <select
                    name="estado"
                    id="estado"
                    required
                >

                    <option
                        value="1"
                        <?php
                        echo ((string) $atributo["estado"] === "1")
                            ? "selected"
                            : "";
                        ?>
                    >
                        Activa
                    </option>

                    <option
                        value="0"
                        <?php
                        echo ((string) $atributo["estado"] === "0")
                            ? "selected"
                            : "";
                        ?>
                    >
                        Inactiva
                    </option>

                </select>


                <br><br>


                <button
                    type="submit"
                    class="btn-nuevo"
                >
                    Guardar cambios
                </button>

            </form>

        </section>

    </main>

</div>


<!-- =================================
     JAVASCRIPT DEL DASHBOARD
     ================================= -->

<script src="../dashboard/dashboard.js"></script>


<!-- =================================
     CONTADOR DE CARACTERES
     ================================= -->

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

nombre.addEventListener(
    "input",
    actualizarContadorNombre
);

actualizarContadorNombre();

</script>

</body>

</html>