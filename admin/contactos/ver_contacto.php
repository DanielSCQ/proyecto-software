<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");
require_once("../../config/correo.php");


// =================================
// TOKEN CSRF
// =================================

if (
    !isset($_SESSION["csrf_respuesta_contacto"]) ||
    !is_string($_SESSION["csrf_respuesta_contacto"])
) {
    $_SESSION["csrf_respuesta_contacto"] =
        bin2hex(random_bytes(32));
}


// =================================
// VALIDAR ID DEL CONTACTO
// =================================

$id_contacto = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id_contacto || $id_contacto < 1) {
    header("Location: contactos.php");
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
        WHERE id_contacto = ?
        LIMIT 1";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    header("Location: contactos.php");
    exit();
}

$stmt->bind_param(
    "i",
    $id_contacto
);

$stmt->execute();

$resultado = $stmt->get_result();


// =================================
// VERIFICAR SI EXISTE
// =================================

if ($resultado->num_rows === 0) {

    $stmt->close();

    header("Location: contactos.php");
    exit();
}

$contacto = $resultado->fetch_assoc();

$stmt->close();


// =================================
// MARCAR COMO LEÍDO
// =================================

if ($contacto["estado"] === "pendiente") {

    $sql_leido = "
        UPDATE contactos
        SET estado = 'leido'
        WHERE id_contacto = ?
        AND estado = 'pendiente'
    ";

    $stmt_leido = $conexion->prepare($sql_leido);

    if ($stmt_leido) {

        $stmt_leido->bind_param(
            "i",
            $id_contacto
        );

        $stmt_leido->execute();
        $stmt_leido->close();

        $contacto["estado"] = "leido";
    }
}


// =================================
// VARIABLES
// =================================

$errores = [];

$respuestaFormulario =
    $contacto["respuesta"] ?? "";


// =================================
// FUNCIÓN PARA LONGITUD
// =================================

function longitudRespuestaContacto(
    string $texto
): int {

    return function_exists("mb_strlen")
        ? mb_strlen($texto, "UTF-8")
        : strlen($texto);
}


// =================================
// PROCESAR ACCIONES
// =================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ---------------------------------
    // VALIDAR CSRF
    // ---------------------------------

    $csrf =
        $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrf) ||
        !hash_equals(
            $_SESSION["csrf_respuesta_contacto"],
            $csrf
        )
    ) {

        $errores[] =
            "La solicitud no es válida. Recarga la página e inténtalo nuevamente.";
    }


    // ---------------------------------
    // IDENTIFICAR ACCIÓN
    // ---------------------------------

    $accion =
        $_POST["accion"] ?? "";


    // =================================
    // ACCIÓN: ENVIAR RESPUESTA
    // =================================

    if (
        empty($errores) &&
        $accion === "responder"
    ) {

        // ---------------------------------
        // SOLO PERMITIR SI NO ESTÁ CERRADO
        // ---------------------------------

        if ($contacto["estado"] === "cerrado") {

            $errores[] =
                "Este contacto ya está cerrado.";

        } else {

            // ---------------------------------
            // RECIBIR RESPUESTA
            // ---------------------------------

            $respuestaFormulario =
                trim($_POST["respuesta"] ?? "");


            // ---------------------------------
            // VALIDAR RESPUESTA
            // ---------------------------------

            if ($respuestaFormulario === "") {

                $errores[] =
                    "Debes escribir una respuesta antes de enviarla.";

            } elseif (
                longitudRespuestaContacto(
                    $respuestaFormulario
                ) > 3000
            ) {

                $errores[] =
                    "La respuesta no puede superar los 3000 caracteres.";
            }


            // ---------------------------------
            // VALIDAR CORREO
            // ---------------------------------

            $correoDestino =
                trim($contacto["correo"] ?? "");

            if (
                $correoDestino === "" ||
                !filter_var(
                    $correoDestino,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                $errores[] =
                    "El contacto no tiene un correo electrónico válido.";
            }


            // =================================
            // ENVIAR CORREO
            // =================================

            if (empty($errores)) {

                $correoEnviado =
                    enviarRespuestaContacto(
                        $correoDestino,
                        $contacto["nombre"],
                        $contacto["asunto"],
                        $contacto["mensaje"],
                        $respuestaFormulario
                    );


                // =================================
                // CORREO ENVIADO
                // =================================

                if ($correoEnviado) {

                    $estadoRespondido =
                        "respondido";


                    // ---------------------------------
                    // GUARDAR RESPUESTA
                    // ---------------------------------

                    $sql_actualizar = "
                        UPDATE contactos
                        SET
                            respuesta = ?,
                            estado = ?
                        WHERE id_contacto = ?
                        AND estado <> 'cerrado'
                    ";

                    $stmt_actualizar =
                        $conexion->prepare(
                            $sql_actualizar
                        );


                    if (!$stmt_actualizar) {

                        $errores[] =
                            "El correo fue enviado, pero no fue posible actualizar el contacto.";

                    } else {

                        $stmt_actualizar->bind_param(
                            "ssi",
                            $respuestaFormulario,
                            $estadoRespondido,
                            $id_contacto
                        );


                        if ($stmt_actualizar->execute()) {

                            $stmt_actualizar->close();


                            // ---------------------------------
                            // RENOVAR TOKEN
                            // ---------------------------------

                            $_SESSION["csrf_respuesta_contacto"] =
                                bin2hex(
                                    random_bytes(32)
                                );


                            // ---------------------------------
                            // REDIRECCIÓN PRG
                            // ---------------------------------

                            header(
                                "Location: ver_contacto.php?id="
                                . $id_contacto
                                . "&enviado=1"
                            );

                            exit();

                        } else {

                            $errores[] =
                                "El correo fue enviado, pero no fue posible guardar la respuesta.";

                            $stmt_actualizar->close();
                        }
                    }

                } else {

                    $errores[] =
                        "No fue posible enviar la respuesta por correo. Inténtalo nuevamente.";
                }
            }
        }
    }


    // =================================
    // ACCIÓN: CERRAR CONTACTO
    // =================================

    elseif (
        empty($errores) &&
        $accion === "cerrar"
    ) {

        // ---------------------------------
        // SOLO SE CIERRA SI ESTÁ RESPONDIDO
        // ---------------------------------

        if ($contacto["estado"] !== "respondido") {

            $errores[] =
                "Solo se puede cerrar un contacto que ya haya sido respondido.";

        } else {

            $estadoCerrado =
                "cerrado";


            $sql_cerrar = "
                UPDATE contactos
                SET estado = ?
                WHERE id_contacto = ?
                AND estado = 'respondido'
            ";

            $stmt_cerrar =
                $conexion->prepare(
                    $sql_cerrar
                );


            if (!$stmt_cerrar) {

                $errores[] =
                    "No fue posible cerrar el contacto.";

            } else {

                $stmt_cerrar->bind_param(
                    "si",
                    $estadoCerrado,
                    $id_contacto
                );


                if ($stmt_cerrar->execute()) {

                    $stmt_cerrar->close();


                    // ---------------------------------
                    // RENOVAR TOKEN
                    // ---------------------------------

                    $_SESSION["csrf_respuesta_contacto"] =
                        bin2hex(
                            random_bytes(32)
                        );


                    // ---------------------------------
                    // REDIRECCIÓN PRG
                    // ---------------------------------

                    header(
                        "Location: ver_contacto.php?id="
                        . $id_contacto
                        . "&cerrado=1"
                    );

                    exit();

                } else {

                    $errores[] =
                        "No fue posible cerrar el contacto.";

                    $stmt_cerrar->close();
                }
            }
        }
    }


    // =================================
    // ACCIÓN NO VÁLIDA
    // =================================

    elseif (
        empty($errores) &&
        !in_array(
            $accion,
            ["responder", "cerrar"],
            true
        )
    ) {

        $errores[] =
            "La acción solicitada no es válida.";
    }
}


// =================================
// RECARGAR CONTACTO DESPUÉS DE ACCIÓN
// =================================

if (
    (
        isset($_GET["enviado"]) &&
        $_GET["enviado"] === "1"
    ) ||
    (
        isset($_GET["cerrado"]) &&
        $_GET["cerrado"] === "1"
    )
) {

    $sql_recargar = "
        SELECT
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
        WHERE id_contacto = ?
        LIMIT 1
    ";

    $stmt_recargar =
        $conexion->prepare(
            $sql_recargar
        );

    if ($stmt_recargar) {

        $stmt_recargar->bind_param(
            "i",
            $id_contacto
        );

        $stmt_recargar->execute();

        $resultado_recargar =
            $stmt_recargar->get_result();

        if (
            $resultado_recargar->num_rows === 1
        ) {

            $contacto =
                $resultado_recargar->fetch_assoc();

            $respuestaFormulario =
                $contacto["respuesta"] ?? "";
        }

        $stmt_recargar->close();
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ver contacto | AGRANDA</title>

    <link
        rel="stylesheet"
        href="contactos.css"
    >

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

                    <span id="fecha"></span>
                    <br>
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
             VER CONTACTO
        ================================== -->

        <section class="contenido-contactos">


            <div class="encabezado-contactos">

                <div>

                    <h2>
                        ✉️ Detalle del contacto
                    </h2>

                    <p>
                        Consulta la información y responde
                        el mensaje recibido.
                    </p>

                </div>

            </div>


            <!-- =================================
                 RESPUESTA ENVIADA
            ================================== -->

            <?php if (
                isset($_GET["enviado"]) &&
                $_GET["enviado"] === "1"
            ): ?>

                <div
                    class="mensaje-exito"
                    role="status"
                >
                    ✓ La respuesta fue enviada al correo
                    del contacto correctamente.
                </div>

            <?php endif; ?>


            <!-- =================================
                 CONTACTO CERRADO
            ================================== -->

            <?php if (
                isset($_GET["cerrado"]) &&
                $_GET["cerrado"] === "1"
            ): ?>

                <div
                    class="mensaje-exito"
                    role="status"
                >
                    ✓ El contacto fue cerrado correctamente.
                </div>

            <?php endif; ?>


            <!-- =================================
                 ERRORES
            ================================== -->

            <?php if (!empty($errores)): ?>

                <div
                    class="mensaje-error"
                    role="alert"
                >

                    <ul>

                        <?php foreach (
                            $errores as $error
                        ): ?>

                            <li>
                                <?=
                                htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                                ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- =================================
                 DETALLE
            ================================== -->

            <div class="detalle-contacto">


                <div class="informacion-contacto">


                    <div class="campo-contacto">

                        <span>Nombre</span>

                        <strong>
                            <?=
                            htmlspecialchars(
                                $contacto["nombre"],
                                ENT_QUOTES,
                                "UTF-8"
                            )
                            ?>
                        </strong>

                    </div>


                    <div class="campo-contacto">

                        <span>Correo</span>

                        <strong>
                            <?=
                            htmlspecialchars(
                                $contacto["correo"],
                                ENT_QUOTES,
                                "UTF-8"
                            )
                            ?>
                        </strong>

                    </div>


                    <div class="campo-contacto">

                        <span>Teléfono</span>

                        <strong>

                            <?php
                            echo !empty(
                                $contacto["telefono"]
                            )
                                ? htmlspecialchars(
                                    $contacto["telefono"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
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
                                strtotime(
                                    $contacto["fecha_envio"]
                                )
                            );
                            ?>

                        </strong>

                    </div>


                    <div class="campo-contacto">

                        <span>Asunto</span>

                        <strong>
                            <?=
                            htmlspecialchars(
                                $contacto["asunto"],
                                ENT_QUOTES,
                                "UTF-8"
                            )
                            ?>
                        </strong>

                    </div>


                    <div class="campo-contacto">

                        <span>Estado</span>

                        <strong>

                            <?php

                            switch ($contacto["estado"]) {

                                case "pendiente":

                                    echo '
                                        <span
                                            class="
                                                estado-cliente
                                                estado-inactivo
                                            "
                                        >
                                            Pendiente
                                        </span>
                                    ';

                                    break;


                                case "leido":

                                    echo '
                                        <span
                                            class="estado-cliente"
                                        >
                                            Leído
                                        </span>
                                    ';

                                    break;


                                case "respondido":

                                    echo '
                                        <span
                                            class="
                                                estado-cliente
                                                estado-activo
                                            "
                                        >
                                            Respondido
                                        </span>
                                    ';

                                    break;


                                case "cerrado":

                                    echo '
                                        <span
                                            class="estado-cliente"
                                        >
                                            Cerrado
                                        </span>
                                    ';

                                    break;
                            }

                            ?>

                        </strong>

                    </div>

                </div>


                <!-- =================================
                     MENSAJE ORIGINAL
                ================================== -->

                <div class="mensaje-contacto">

                    <h3>
                        💬 Mensaje
                    </h3>

                    <div class="contenido-mensaje">

                        <p>

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $contacto["mensaje"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                            );
                            ?>

                        </p>

                    </div>

                </div>


                <!-- =================================
                     CONTACTO NO CERRADO
                ================================== -->

                <?php if (
                    $contacto["estado"] !== "cerrado"
                ): ?>


                    <!-- =================================
                         FORMULARIO RESPUESTA
                    ================================== -->

                    <form
                        method="POST"
                        class="formulario-respuesta"
                        autocomplete="off"
                    >

                        <h3>
                            ✍️ Responder contacto
                        </h3>


                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?=
                            htmlspecialchars(
                                $_SESSION[
                                    "csrf_respuesta_contacto"
                                ],
                                ENT_QUOTES,
                                "UTF-8"
                            )
                            ?>"
                        >


                        <input
                            type="hidden"
                            name="accion"
                            value="responder"
                        >


                        <label for="respuesta">
                            Respuesta
                        </label>


                        <textarea
                            name="respuesta"
                            id="respuesta"
                            rows="7"
                            maxlength="3000"
                            required
                            placeholder="Escriba aquí la respuesta..."
                        ><?= htmlspecialchars(
                            $respuestaFormulario,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?></textarea>


                        <div class="acciones-contacto">

                            <a
                                href="contactos.php"
                                class="btn-cancelar"
                            >
                                ← Volver
                            </a>


                            <button
                                type="submit"
                                class="btn-ver"
                            >
                                ✉️ Enviar respuesta
                            </button>

                        </div>

                    </form>


                    <!-- =================================
                         CERRAR CONTACTO
                    ================================== -->

                    <?php if (
                        $contacto["estado"] === "respondido"
                    ): ?>

                        <form
                            method="POST"
                            class="formulario-respuesta"
                        >

                            <h3>
                                ✓ Finalizar atención
                            </h3>

                            <p>
                                La consulta ya fue respondida.
                                Cuando consideres que el caso está
                                terminado, puedes cerrar el contacto.
                            </p>


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?=
                                htmlspecialchars(
                                    $_SESSION[
                                        "csrf_respuesta_contacto"
                                    ],
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                                ?>"
                            >


                            <input
                                type="hidden"
                                name="accion"
                                value="cerrar"
                            >


                            <div class="acciones-contacto">

                                <button
                                    type="submit"
                                    class="btn-ver"
                                >
                                    ✓ Cerrar contacto
                                </button>

                            </div>

                        </form>

                    <?php endif; ?>


                <?php else: ?>


                    <!-- =================================
                         CONTACTO YA CERRADO
                    ================================== -->

                    <div class="formulario-respuesta">

                        <h3>
                            ✓ Contacto cerrado
                        </h3>

                        <p>
                            Esta consulta ya fue atendida
                            y se encuentra cerrada.
                        </p>


                        <?php if (
                            !empty($contacto["respuesta"])
                        ): ?>

                            <label>
                                Respuesta enviada
                            </label>

                            <div class="contenido-mensaje">

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $contacto["respuesta"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                );
                                ?>

                            </div>

                        <?php endif; ?>


                        <div class="acciones-contacto">

                            <a
                                href="contactos.php"
                                class="btn-cancelar"
                            >
                                ← Volver
                            </a>

                        </div>

                    </div>


                <?php endif; ?>


            </div>

        </section>

    </main>

</div>


<script src="../dashboard/dashboard.js"></script>

</body>

</html>