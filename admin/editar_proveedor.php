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


// Verificar ID de proveedor

if (!isset($_GET['id'])) {

    header("Location: proveedores.php");
    exit();

}


$id_proveedor = $_GET['id'];



// Actualizar proveedores

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $nombre = $_POST["nombre"];
    $nit = $_POST["nit"];
    $telefono = $_POST["telefono"];
    $correo = $_POST["correo"];
    $direccion = $_POST["direccion"];
    $contacto_principal = $_POST["contacto_principal"];
    $estado = $_POST["estado"];

    $sql = "UPDATE proveedores
            SET nombre = ?,
                nit = ?,
                telefono = ?,
                correo = ?,
                direccion = ?,
                contacto_principal = ?,
                estado = ?
            WHERE id_proveedor = ?";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "ssssssii",
        $nombre,
        $nit,
        $telefono,
        $correo,
        $direccion,
        $contacto_principal,
        $estado,
        $id_proveedor
    );


    if ($stmt->execute()) {


        header("Location: proveedores.php");
        exit();


    } else {


        echo "Error al actualizar proveedores";


    }


}



// Obtener datos actuales

$sql = "SELECT * FROM proveedores WHERE id_proveedor = ?";


$stmt = $conexion->prepare($sql);


$stmt->bind_param("i", $id_proveedor);


$stmt->execute();


$resultado = $stmt->get_result();

$proveedor = $resultado->fetch_assoc();

if (!$proveedor) {
    header("Location: proveedores.php");
    exit();
}



?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Editar Proveedores</title>

    <link rel="stylesheet" href="productos.css">
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

                <a href="cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>
            </div>

        </header>


        <div class="principal">

        <h2 class="titulo-formulario">
            ✏️ Editar Proveedores
        </h2>

        <form method="POST">

            <label>Nombre del proveedor</label>
            <input
                type="text"
                name="nombre"
                value="<?php echo $proveedor['nombre']; ?>"
                required>

            <label>NIT</label>
            <input
                type="text"
                name="nit"
                value="<?php echo $proveedor['nit']; ?>"
                required>

            <label>Teléfono</label>
            <input
                type="text"
                name="telefono"
                value="<?php echo $proveedor['telefono']; ?>">

            <label>Correo electrónico</label>
            <input
                type="email"
                name="correo"
                value="<?php echo $proveedor['correo']; ?>">

            <label>Dirección</label>
            <textarea
                name="direccion"
                rows="3"><?php echo $proveedor['direccion']; ?></textarea>

            <label>Contacto principal</label>
            <input
                type="text"
                name="contacto_principal"
                value="<?php echo $proveedor['contacto_principal']; ?>">

            <label>Estado</label>

            <select name="estado">

                <option value="1"
                    <?php if($proveedor['estado']==1) echo "selected"; ?>>
                    Activo
                </option>

                <option value="0"
                    <?php if($proveedor['estado']==0) echo "selected"; ?>>
                    Inactivo
                </option>

            </select>

            <br><br>

            <button
                class="btn-actualizar"
                type="submit">

                💾 Actualizar Proveedor

            </button>

            <a href="proveedores.php" class="btn-cancelar">
                Cancelar
            </a>

        </form>
    </div>
</main>
</div>
</body>
</html>