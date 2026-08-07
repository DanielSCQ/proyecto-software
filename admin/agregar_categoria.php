<?php

session_start();
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once("../config/conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = $_POST["nombre"];
    $descripcion = $_POST["descripcion"];
    $estado = $_POST["estado"];

    $imagen = "";

    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] == 0) {

        $carpeta = "../uploads/categorias/";

        $nombreImagen = time() . "_" . basename($_FILES["imagen"]["name"]);

        $rutaDestino = $carpeta . $nombreImagen;

        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaDestino)) {

            $imagen = "uploads/categorias/" . $nombreImagen;

        }

    }

    $sql = "INSERT INTO categorias(nombre, descripcion, imagen, estado)
            VALUES (?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param("sssi", $nombre, $descripcion, $imagen, $estado);

    if ($stmt->execute()) {

        header("Location: categorias.php");
        exit();

    } else {

        echo "Error al guardar la categoría.";

    }

}

?>

<!DOCTYPE html>
<html lang="es">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nueva Categoría</title>

    <link rel="stylesheet" href="productos.css">

</head>

<body>

    <div class="contenedor-dashboard">

    <!-- Menú lateral -->
        <aside class="menu-lateral">

            <div class="logo-panel">

                <h1>AGRANDA</h1>

                <p>productos</p>

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

                    <a href="cerrar_sesion.php" class="btn-salir">
                        Cerrar sesión
                    </a>

                </div>

            </header>

    <div class="principal">

    <h2>➕ Nueva Categoría</h2>

    <form method="POST" enctype="multipart/form-data">

        <p>
            <label>Nombre</label><br>

            <input
                type="text"
                name="nombre"
                required
                style="width:100%;padding:10px;">

        </p>

        <p>

            <label>Descripción</label><br>

                <textarea
                    name="descripcion"
                    rows="5"
                    style="width:100%;padding:10px;"></textarea>
        </p>

        <p>

            <label>imagen</label><br>

                <input
                    type="file"
                    name="imagen"
                    accept=".jpg,.jpeg,.png,.webp"
                    require>
        </p>

        <p>
            <label>Estado</label><br>

            <select name="estado">

            <option value="1">Activa</option>

            <option value="0">Inactiva</option>

            </select>

        </p>

        <p>

        <br></br>
            <button type="submit" class="btn-nuevo">
                💾 Guardar Categoría
            </button>

            <a href="categorias.php" class="btn-cancelar">
                Cancelar
            </a>

        </p>

    </form>
    </div>
    </div>
</body>
</html>