<?php

session_start();

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");
    exit();

}


require_once("../../config/conexion.php");


// Verificar ID de categoría

if (!isset($_GET["id"]) || !ctype_digit($_GET["id"])) {

    header("Location: marcas.php");
    exit();

}

$id_marca = (int) $_GET["id"];

// Actualizar categoría

$errores = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $estado = $_POST["estado"] ?? "";

    if ($nombre === "") {

        $errores[] = "El nombre de la marca es obligatorio.";

    } elseif (mb_strlen($nombre) > 150) {

        $errores[] = "El nombre de la marca no puede superar los 150 caracteres.";

    }

    if (mb_strlen($descripcion) > 300) {

        $errores[] = "La descripción no puede superar los 300 caracteres.";

    }

    if ($estado !== "0" && $estado !== "1") {

        $errores[] = "El estado seleccionado no es válido.";

    }

    if (empty($errores)) {

        $sql = "UPDATE marcas
                SET nombre = ?,
                    descripcion = ?,
                    estado = ?
                WHERE id_marca = ?";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {

            $errores[] = "No fue posible preparar la actualización.";

        } else {

            $estadoInt = (int) $estado;

            $stmt->bind_param(
                "ssii",
                $nombre,
                $descripcion,
                $estadoInt,
                $id_marca
            );

            if ($stmt->execute()) {

                $stmt->close();

                header("Location: marcas.php");
                exit();

            } else {

                $errores[] = "No fue posible actualizar la marca.";

            }

            $stmt->close();

        }
    }
}


// Obtener datos actuales

$sql = "SELECT * FROM marcas WHERE id_marca = ?";

$stmt = $conexion->prepare($sql);

$stmt->bind_param("i", $id_marca);

$stmt->execute();

$resultado = $stmt->get_result();

$marca = $resultado->fetch_assoc();

if (!$marca) {
    header("Location: marcas.php");
    exit();
}

$nombre = $marca["nombre"];
$descripcion = $marca["descripcion"];
$estado = (string) $marca["estado"];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Editar Marca</title>

    <link rel="stylesheet" href="marcas.css">
</head>
<body>

    <div class="contenedor-dashboard">
    <!-- MENÚ LATERAL -->

        <aside class="menu-lateral">
            <div class="logo-panel">
                <h1>AGRANDA</h1>

                <p>Panel administrativo</p>

    </div>
    <nav>
        <ul>

            <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="marcas.php" class="activo">🏷️ Marcas</a></li>

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
        <!-- CONTENIDO -->
        <main class="contenido">

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>
                    ¡Bienvenido, 
                    <?php echo $_SESSION["nombre"]; ?>!
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

                👤 <?php echo $_SESSION["nombre"]; ?>

            </div>

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>
            </div>

        </header>


        <div class="principal">

        <h2 class="titulo-formulario">✏️ Editar Marca</h2>

        <?php if (!empty($errores)) { ?>

            <div class="mensaje-error">

                <strong>🛑 No se puede actualizar la marca.</strong>

                <ul>

                    <?php foreach ($errores as $error) { ?>

                        <li>
                            <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
                        </li>

                    <?php } ?>

                </ul>

            </div>

        <?php } ?>

        <form method="POST">

        <label>Nombre</label>

            <input
                type="text"
                name="nombre"
                id="nombre"
                maxlength="150"
                value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, "UTF-8"); ?>"
                required
            >

            <div
                id="contador-nombre"
                style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                0 / 150 caracteres
            </div>

        <label>Descripción</label>


        <textarea
            name="descripcion"
            id="descripcion"
            rows="5"
            maxlength="300"><?php echo htmlspecialchars($descripcion, ENT_QUOTES, "UTF-8"); ?></textarea>

        <div
            id="contador-descripcion"
            style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
            0 / 300 caracteres
        </div>

        <label>Estado</label>

        <select name="estado">

        <option value="1"
            <?php if($marca['estado']==1) echo "selected"; ?>>Activa
        </option>

        <option value="0"
            <?php if($marca['estado']==0) echo "selected"; ?>>Inactiva
        </option>


    </select>

    <br><br>

        <button 
            class="btn-actualizar"
            type="submit">

            💾 Actualizar Marca

        </button>


        <a href="marcas.php" class="btn-cancelar">
            Cancelar
        </a>
    </form>
    </div>
</main>
</div>

<script src="../dashboard/dashboard.js"></script>

<script>

const nombre = document.getElementById("nombre");
const contadorNombre = document.getElementById("contador-nombre");

const descripcion = document.getElementById("descripcion");
const contadorDescripcion = document.getElementById("contador-descripcion");

const formulario = document.querySelector("form");


function actualizarContadorNombre() {

    const cantidad = nombre.value.length;

    contadorNombre.textContent =
        cantidad + " / 150 caracteres";

    if (cantidad >= 150) {

        contadorNombre.style.color = "#c62828";
        contadorNombre.style.fontWeight = "bold";

    } else {

        contadorNombre.style.color = "#666";
        contadorNombre.style.fontWeight = "normal";

    }

}


function actualizarContadorDescripcion() {

    const cantidad = descripcion.value.length;

    contadorDescripcion.textContent =
        cantidad + " / 300 caracteres";

    if (cantidad >= 300) {

        contadorDescripcion.style.color = "#c62828";
        contadorDescripcion.style.fontWeight = "bold";

    } else {

        contadorDescripcion.style.color = "#666";
        contadorDescripcion.style.fontWeight = "normal";

    }

}


nombre.addEventListener("input", actualizarContadorNombre);

descripcion.addEventListener("input", actualizarContadorDescripcion);


actualizarContadorNombre();
actualizarContadorDescripcion();


formulario.addEventListener("submit", function(event) {

    if (nombre.value.length > 150) {

        event.preventDefault();

        alert("🛑 El nombre de la marca no puede superar los 150 caracteres.");

        nombre.focus();

        return;

    }

    if (descripcion.value.length > 300) {

        event.preventDefault();

        alert("🛑 La descripción no puede superar los 300 caracteres.");

        descripcion.focus();

        return;

    }

});

</script>
</body>
</html>