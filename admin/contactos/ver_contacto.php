<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// VALIDAR ID DEL CONTACTO
// =================================

$id_contacto = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id_contacto) {
    header("Location: contactos.php");
    exit();
}


// =================================
// PROCESAR RESPUESTA
// =================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $respuesta = trim($_POST["respuesta"] ?? "");
    $estado_nuevo = $_POST["estado"] ?? "";

    $estados_validos = [
        "pendiente",
        "leido",
        "respondido",
        "cerrado"
    ];

    if (!in_array($estado_nuevo, $estados_validos, true)) {
        $estado_nuevo = "leido";
    }

    $sql_actualizar = "UPDATE contactos
                       SET respuesta = ?, estado = ?
                       WHERE id_contacto = ?";

    $stmt_actualizar = $conexion->prepare($sql_actualizar);

    if (!$stmt_actualizar) {
        die("Error al actualizar el contacto: " . $conexion->error);
    }

    $stmt_actualizar->bind_param(
        "ssi",
        $respuesta,
        $estado_nuevo,
        $id_contacto
    );

    $stmt_actualizar->execute();

    $stmt_actualizar->close();

    header("Location: ver_contacto.php?id=" . $id_contacto . "&guardado=1");
    exit();
}


// =================================
// CONSULTAR CONTACTO
// =================================

$sql = "SELECT
            id_contacto,
            id_usuario,
            nombre,
            correo,
            telefono,
            asunto,
            mensaje,
            fecha_envio,
            estado,
            respuesta
        FROM contactos
        WHERE id_contacto = ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error en la consulta: " . $conexion->error);
}

$stmt->bind_param("i", $id_contacto);

$stmt->execute();

$resultado = $stmt->get_result();


// =================================
// VERIFICAR SI EXISTE
// =================================

if ($resultado->num_rows === 0) {
    header("Location: contactos.php");
    exit();
}

$contacto = $resultado->fetch_assoc();

$stmt->close();


// =================================
// MARCAR COMO LEÍDO
// =================================

if ($contacto["estado"] === "pendiente") {

    $sql_leido = "UPDATE contactos
                  SET estado = 'leido'
                  WHERE id_contacto = ?";

    $stmt_leido = $conexion->prepare($sql_leido);

    if ($stmt_leido) {

        $stmt_leido->bind_param("i", $id_contacto);
        $stmt_leido->execute();
        $stmt_leido->close();

        $contacto["estado"] = "leido";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ver contacto | AGRANDA</title>

    <link rel="stylesheet" href="contactos.css">

</head>


<body>

<div class="contenedor-dashboard">


    <!-- =================================
         MENÚ LATERAL
    ================================== -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Contactos</p>

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

                <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                <li><a href="contactos.php" class="activo">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

            </ul>

        </nav>

    </aside>


    <!-- =================================
         CONTENIDO
    ================================== -->

    <main class="contenido">


        <!-- =================================
             ENCABEZADO
        ================================== -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>
                    ¡Bienvenido,
                    <?php echo htmlspecialchars($_SESSION["nombre"]); ?>!
                </h2>

                <p>Panel de Administración AGRANDA</p>

            </div>


            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>

                <div class="usuario">
                    👤<?php echo htmlspecialchars($_SESSION["nombre"]); ?>
                </div>

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>


        <!-- =================================
             VER CONTACTO
        ================================== -->

        <section class="contenido-contactos">


            <div class="encabezado-contactos">

                <div>

                    <h2>✉️ Detalle del contacto</h2>

                    <p>
                        Consulta la información y responde el mensaje recibido.
                    </p>

                </div>

            </div>


            <?php if (isset($_GET["guardado"])): ?>

                <div class="mensaje-exito">
                    ✓ El contacto fue actualizado correctamente.
                </div>

            <?php endif; ?>


            <!-- =================================
                 INFORMACIÓN DEL CONTACTO
            ================================== -->

            <div class="detalle-contacto">


                <div class="informacion-contacto">


                    <div class="campo-contacto">

                        <span>Nombre</span>

                        <strong>
                            <?php echo htmlspecialchars($contacto["nombre"]); ?>
                        </strong>

                    </div>


                    <div class="campo-contacto">

                        <span>Correo</span>

                        <strong>
                            <?php echo htmlspecialchars($contacto["correo"]); ?>
                        </strong>

                    </div>


                    <div class="campo-contacto">

                        <span>Teléfono</span>

                        <strong>
                            <?php
                            echo !empty($contacto["telefono"])
                                ? htmlspecialchars($contacto["telefono"])
                                : "No registrado";
                            ?>
                        </strong>

                    </div>


                    <div class="campo-contacto">

                        <span>Fecha de envío</span>

                        <strong>

                            <?php
                            echo date(
                                "d/m/Y H:i",
                                strtotime($contacto["fecha_envio"])
                            );
                            ?>

                        </strong>

                    </div>


                    <div class="campo-contacto">

                        <span>Asunto</span>

                        <strong>
                            <?php echo htmlspecialchars($contacto["asunto"]); ?>
                        </strong>

                    </div>


                    <div class="campo-contacto">

                        <span>Estado</span>

                        <strong>

                            <?php

                            switch ($contacto["estado"]) {

                                case "pendiente":
                                    echo '<span class="estado-cliente estado-inactivo">
                                            Pendiente
                                          </span>';
                                    break;

                                case "leido":
                                    echo '<span class="estado-cliente">
                                            Leído
                                          </span>';
                                    break;

                                case "respondido":
                                    echo '<span class="estado-cliente estado-activo">
                                            Respondido
                                          </span>';
                                    break;

                                case "cerrado":
                                    echo '<span class="estado-cliente">
                                            Cerrado
                                          </span>';
                                    break;
                            }

                            ?>

                        </strong>

                    </div>

                </div>


                <!-- =================================
                     RESPONDER
                ================================== -->

                <div class="mensaje-contacto">

                    <h3>💬 Mensaje</h3>

                    <div class="contenido-mensaje">

                        <?php
                        /*
                         * IMPORTANTE:
                         * Aquí mostramos el mensaje que viene
                         * almacenado en la base de datos.
                         *
                         * Si tu tabla contactos actualmente no tiene
                         * una columna llamada "mensaje", debemos agregarla.
                         */
                        ?>

                        <p>
                            <?php
                            if (isset($contacto["mensaje"])) {
                                echo nl2br(
                                    htmlspecialchars($contacto["mensaje"])
                                );
                            } else {
                                echo "No hay mensaje registrado.";
                            }
                            ?>
                        </p>

                    </div>

                </div>


                <!-- =================================
                     FORMULARIO DE RESPUESTA
                ================================== -->

                <form method="POST" class="formulario-respuesta">

                    <h3>✍️ Responder contacto</h3>


                    <label for="respuesta">
                        Respuesta
                    </label>

                    <textarea
                        name="respuesta"
                        id="respuesta"
                        rows="7"
                        placeholder="Escriba aquí la respuesta..."
                    ><?php echo htmlspecialchars($contacto["respuesta"] ?? ""); ?></textarea>


                    <label for="estado">
                        Estado
                    </label>

                    <select name="estado" id="estado">

                        <option
                            value="leido"
                            <?php echo $contacto["estado"] === "leido" ? "selected" : ""; ?>
                        >
                            Leído
                        </option>

                        <option
                            value="respondido"
                            <?php echo $contacto["estado"] === "respondido" ? "selected" : ""; ?>
                        >
                            Respondido
                        </option>

                        <option
                            value="cerrado"
                            <?php echo $contacto["estado"] === "cerrado" ? "selected" : ""; ?>
                        >
                            Cerrado
                        </option>

                    </select>

                    <div class="acciones-contacto">

                        <a href="contactos.php"
                            class="btn-cancelar">← Volver</a>

                        <button type="submit"
                            class="btn-ver">💾 Guardar respuesta</button>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

<script src="../dashboard/dashboard.js"></script>
</body>
</html>