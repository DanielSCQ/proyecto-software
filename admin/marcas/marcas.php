<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

?>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") { 

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $estado = $_POST["estado"] ?? "";

    $errores = [];

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

        $sql = "INSERT INTO marcas (nombre, descripcion, estado)
            VALUES (?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {

            $errores[] = "No fue posible preparar el registro de la marca.";

        } else {

            $estadoInt = (int) $estado;

            $stmt->bind_param(
                "ssi",
                $nombre,
                $descripcion,
                $estadoInt
            );

            if ($stmt->execute()) {

                header("Location: marcas.php");
                exit();

            } else {

                $errores[] = "No fue posible guardar la marca.";

            }

            $stmt->close();

        }
    }
}
    $sql = "SELECT * FROM marcas ORDER BY id_marca DESC";

    $resultado = $conexion->query($sql);


?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Marcas | AGRANDA</title>

    <link rel="stylesheet" href="marcas.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- Menú lateral -->
    <aside class="menu-lateral">

        <div class="logo-panel">
            <h1>AGRANDA</h1>
            <p>Marcas</p>
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

    <!-- Contenido principal -->
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

        <!-- Área de trabajo -->
        <section class="resumen marcas-contenedor">

            <h2>🏷️ Gestor de Marcas</h2>

            <p>
                Desde aquí podrás administrar las marcas de AGRANDA.
            </p>

            <?php if (!empty($errores)) { ?>

                <div class="mensaje-error">

                    <strong>🛑 No se puede guardar la marca.</strong>

                    <ul>

                        <?php foreach ($errores as $error) { ?>

                            <li>
                                <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
                            </li>

                        <?php } ?>

                    </ul>

                </div>

            <?php } ?>

            <form method="POST"class="formulario-marcas">

                <label>Nombre de la marca</label>
                    
                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        maxlength="150"
                        required>

                    <div
                        id="contador-nombre"
                        style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                        0 / 150 caracteres
                    </div>

                <label>Descripción</label>

                    <textarea
                        name="descripcion"
                        id="descripcion"
                        rows="4"
                        maxlength="300"></textarea>

                    <div
                        id="contador-descripcion"
                        style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                        0 / 300 caracteres
                    </div>

                <label>Estado</label>

                <select name="estado">
                    <option value="1">Activa</option>
                    <option value="0">Inactiva</option>
                </select>

                <br><br>

                    <button type="submit" class="btn-nuevo">
                        💾 Guardar Marca
                    </button>

            </form>    
            
            <hr><br>

            <table class="tabla-marcas">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while($marca = $resultado->fetch_assoc()){ ?>

                    <tr>
                        <td><?php echo $marca["id_marca"]; ?></td>
                        <td><?php echo $marca["nombre"]; ?></td>
                        <td><?php echo $marca["descripcion"]; ?></td>
                    
                        <td>

                            <?php if($marca["estado"]){ ?>

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

                            <a href="editar_marca.php?id=<?php echo $marca["id_marca"]; ?>" class="btn-editar">
                                ✏️
                            </a>

                            <a href="eliminar_marca.php?id=<?php echo $marca["id_marca"]; ?>"
                                class="btn-eliminar" onclick="return confirm('¿Deseas eliminar esta marca?');">
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

const descripcion = document.getElementById("descripcion");
const contadorDescripcion = document.getElementById("contador-descripcion");

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

</script>

</body>
</html>