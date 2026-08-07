<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once("../config/conexion.php");

?>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = $_POST["nombre"];
    $descripcion = $_POST["descripcion"];
    $estado = $_POST["estado"];

    $sql = "INSERT INTO marcas (nombre, descripcion, estado)
            VALUES (?, ?, ?)";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param("ssi", $nombre, $descripcion, $estado);

    if ($stmt->execute()) {

        header("Location: marcas.php");
        exit();

    } else {

        echo "Error al guardar la marca.";

    }

}

    $sql = "SELECT * FROM marcas ORDER BY 
    id_marca DESC";

    $resultado =$conexion->query($sql);


?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Marcas | AGRANDA</title>

    <link rel="stylesheet" href="productos.css">

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

        <!-- Área de trabajo -->
        <section class="resumen marcas-contenedor">

            <h2>🏷️ Gestor de Marcas</h2>

            <p>
                Desde aquí podrás administrar las marcas de AGRANDA.
            </p>

            <form method="POST"class="formulario-marcas">

                <label>Nombre de la marca</label>
                    <input
                        type="text"
                        name="nombre"
                        required>

                <label>Descripción</label>
                    <textarea
                        name="descripcion"
                        rows="4"></textarea>

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

<script src="dashboard.js"></script>

</body>
</html>