<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// OBTENER ID DEL CLIENTE
// =================================

$id_cliente = intval($_GET["id"] ?? 0);

if ($id_cliente <= 0) {
    header("Location: clientes.php");
    exit();
}


// =================================
// CONSULTAR CLIENTE
// =================================

$sql = "SELECT
            id_usuario,
            nombre,
            apellido,
            correo,
            telefono,
            estado

        FROM usuarios

        WHERE id_usuario = ?
        AND rol = 'cliente'";


$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error en la consulta: " . $conexion->error);
}

$stmt->bind_param("i", $id_cliente);

$stmt->execute();

$resultado = $stmt->get_result();


// =================================
// VERIFICAR CLIENTE
// =================================

if ($resultado->num_rows === 0) {
    header("Location: clientes.php");
    exit();
}

$cliente = $resultado->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar cliente | AGRANDA</title>

    <link rel="stylesheet" href="clientes.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- =================================
         MENÚ LATERAL
    ================================== -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Clientes</p>

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

                <li><a href="clientes.php" class="activo">👥 Clientes</a></li>

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>

    <!-- =================================
         CONTENIDO PRINCIPAL
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
             EDICIÓN DEL CLIENTE
        ================================== -->

        <section class="contenido-clientes">

            <!-- =================================
                 CABECERA
            ================================== -->

            <div class="encabezado-detalle">

                <div>

                    <h2>✏️ Editar cliente</h2>

                    <p>Modifica la información del cliente seleccionado.</p>

                </div>

                <div class="acciones-detalle">

                    <a href="ver_cliente.php?id=<?php echo $cliente["id_usuario"]; ?>"
                        class="btn-volver">← Volver</a>

                </div>

            </div>

            <!-- =================================
                 FORMULARIO
            ================================== -->

            <div class="tarjeta-detalle">

                <h3>📋 Información del cliente</h3>


                <form
                    action="actualizar_cliente.php"
                    method="POST"
                    class="formulario-cliente"
                >

                    <!-- ID OCULTO -->

                    <input
                        type="hidden"
                        name="id_usuario"
                        value="<?php echo $cliente["id_usuario"]; ?>"
                    >

                    <!-- =================================
                         NOMBRE Y APELLIDO
                    ================================== -->

                    <div class="fila-formulario">

                        <div class="campo-formulario">

                            <label for="nombre">Nombre</label>

                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                maxlength="100"
                                value="<?php echo htmlspecialchars($cliente["nombre"]); ?>"
                                required
                            >

                        </div>

                        <div class="campo-formulario">

                            <label for="apellido">Apellido</label>

                            <input
                                type="text"
                                id="apellido"
                                name="apellido"
                                maxlength="100"
                                value="<?php echo htmlspecialchars($cliente["apellido"]); ?>"
                                required
                            >

                        </div>

                    </div>

                    <!-- =================================
                         CORREO
                    ================================== -->

                    <div class="campo-formulario">

                        <label for="correo">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            maxlength="150"
                            value="<?php echo htmlspecialchars($cliente["correo"]); ?>"
                            required
                        >

                    </div>


                    <!-- =================================
                         TELÉFONO
                    ================================== -->

                    <div class="campo-formulario">

                        <label for="telefono">
                            Teléfono
                        </label>

                        <input
                            type="text"
                            id="telefono"
                            name="telefono"
                            maxlength="20"
                            value="<?php echo htmlspecialchars($cliente["telefono"] ?? ""); ?>"
                        >

                    </div>


                    <!-- =================================
                         ESTADO
                    ================================== -->

                    <div class="campo-formulario">

                        <label for="estado">
                            Estado de la cuenta
                        </label>

                        <select
                            id="estado"
                            name="estado"
                            required
                        >

                            <option
                                value="1"
                                <?php echo $cliente["estado"] ? "selected" : ""; ?>
                            >
                                Activo
                            </option>

                            <option
                                value="0"
                                <?php echo !$cliente["estado"] ? "selected" : ""; ?>
                            >
                                Inactivo
                            </option>

                        </select>

                    </div>


                    <!-- =================================
                         INFORMACIÓN
                    ================================== -->

                    <div class="aviso-edicion">

                        <strong>
                            ℹ️ Información
                        </strong>

                        <p>
                            Los cambios realizados aquí se actualizarán
                            directamente en la cuenta del cliente.
                            El cliente también podrá modificar sus datos
                            desde su cuenta en la tienda.
                        </p>

                    </div>


                    <!-- =================================
                         BOTONES
                    ================================== -->

                    <div class="botones-formulario">

                        <a href="ver_cliente.php?id=<?php echo $cliente["id_usuario"]; ?>"
                            class="btn-cancelar">Cancelar</a>


                        <button type="submit"
                            class="btn-guardar">💾 Guardar cambios</button>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

<script src="../dashboard/dashboard.js"></script>
</body>
</html>