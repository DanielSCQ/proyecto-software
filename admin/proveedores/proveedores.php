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

    $nombre = $_POST["nombre"];
    $nit = $_POST["nit"];
    $telefono = $_POST["telefono"];
    $correo = $_POST["correo"];
    $direccion = $_POST["direccion"];
    $contacto_principal = $_POST["contacto_principal"];
    $estado = $_POST["estado"];

    $sql = "INSERT INTO proveedores
    (nombre, nit, telefono, correo, direccion, contacto_principal, estado)
    VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "ssssssi",
        $nombre,
        $nit,
        $telefono,
        $correo,
        $direccion,
        $contacto_principal,
        $estado
);

    if ($stmt->execute()) {

        header("Location: proveedores.php");
        exit();

    } else {

        echo "Error al guardar el proveedor.";

    }

}

    $sql = "SELECT * FROM proveedores ORDER BY 
    id_proveedor DESC";

    $resultado =$conexion->query($sql);


?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>gestor de proveedores</title>

    <link rel="stylesheet" href="proveedores.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- Menú lateral -->
    <aside class="menu-lateral">

        <div class="logo-panel">
            <h1>AGRANDA</h1>
            <p>proveedores</p>
        </div>

        <nav>
            <ul>

                <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                <li><a href="../productos/productos.php">📦 Productos</a></li>

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="proveedores.php" class="activo">🚚 Proveedores</a></li>

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

            <h2>🚚 Gestor de Proveedores</h2>

            <p>
                Desde aquí podrás administrar los proveedores de AGRANDA.
            </p>

        <form method="POST" class="formulario-proveedores">

                <label>Nombre del proveedor</label>
            <input
                type="text"
                name="nombre"
                required>

            <label>NIT</label>
            <input
                type="text"
                name="nit"
                required>

            <label>Teléfono</label>
            <input
                type="text"
                name="telefono">

            <label>Correo electrónico</label>
            <input
                type="email"
                name="correo">

            <label>Dirección</label>
            <textarea
                name="direccion"
                rows="3"></textarea>

            <label>Contacto principal</label>
            <input
                type="text"
                name="contacto_principal">

            <label>Estado</label>

            <select name="estado">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>

        <br><br>

            <button type="submit" class="btn-nuevo">
                💾 Guardar Proveedor
            </button>

        </form>
            
            <hr><br>

            <table class="tabla-proveedores">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>NIT</th>
                        <th>Telefono</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while($proveedor = $resultado->fetch_assoc()){ ?>

                    <tr>

                        <td><?php echo $proveedor["id_proveedor"]; ?></td>
                        <td><?php echo $proveedor["nombre"]; ?></td>
                        <td><?php echo $proveedor["nit"]; ?></td>
                        <td><?php echo $proveedor["telefono"]; ?></td>
                        <td><?php echo $proveedor["contacto_principal"]; ?></td>

                        <td>

                            <?php if($proveedor["estado"]){ ?>

                                <span class="estado-activo">
                                    Activo
                                </span>

                            <?php } else { ?>

                                <span class="estado-inactivo">
                                    Inactivo
                                </span>

                            <?php } ?>

                        </td>

                        <td class="acciones">

                            <a href="editar_proveedor.php?id=<?php echo $proveedor["id_proveedor"]; ?>" class="btn-editar">
                                ✏️
                            </a>

                            <a href="eliminar_proveedor.php?id=<?php echo $proveedor["id_proveedor"]; ?>"
                            class="btn-eliminar"
                            onclick="return confirm('¿Deseas eliminar este proveedor?');">
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

</body>
</html>