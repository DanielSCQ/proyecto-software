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

if (!isset($_GET['id'])) {

    header("Location: marcas.php");
    exit();

}


$id_marca = $_GET['id'];



// Actualizar categoría

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $nombre = $_POST["nombre"];
    $descripcion = $_POST["descripcion"];
    $estado = $_POST["estado"];


    $sql = "UPDATE marcas 
            SET nombre = ?,
                descripcion = ?,
                estado = ?
            WHERE id_marca = ?";


    $stmt = $conexion->prepare($sql);


    $stmt->bind_param(
        "ssii",
        $nombre,
        $descripcion,
        $estado,
        $id_marca
    );


    if ($stmt->execute()) {


        header("Location: marcas.php");
        exit();


    } else {


        echo "Error al actualizar marcas";


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

        <h2 class="titulo-formulario">
            ✏️ Editar Marca
        </h2>

        <form method="POST">

        <label>
            Nombre
        </label>


            <input 
                type="text"
                name="nombre"
                value="<?php echo $marca['nombre']; ?>"
                required
            >
        <label>
        Descripción
        </label>


        <textarea name="descripcion"rows="5"><?php echo $marca['descripcion']; ?></textarea>

        <label>
            Estado
        </label>

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
</body>
</html>