<?php

session_start();

require_once("../config/conexion.php");


// =================================
// MENSAJES DE ERROR
// =================================

$mensaje = "";

if (isset($_GET["error"])) {

    switch ($_GET["error"]) {

        case "campos":
            $mensaje = "⚠️ Complete todos los campos.";
            break;

        case "correo":
            $mensaje = "❌ El correo no está registrado.";
            break;

        case "clave":
            $mensaje = "❌ Contraseña incorrecta.";
            break;

        case "permisos":
            $mensaje = "🚫 No tiene permisos de administrador.";
            break;

        case "intentos":
            $mensaje = "🔒 Demasiados intentos fallidos. Por seguridad, vuelva a ingresar su correo.";
            break;
    }
}


// =================================
// CONTROL DE INTENTOS
// =================================

if (!isset($_SESSION["intentos"])) {
    $_SESSION["intentos"] = 0;
}


// =================================
// IMÁGENES DEL LOGIN ADMIN
// =================================

$imagenLoginAdmin = "";
$imagenFondoLoginAdmin = "";

$idConfiguracion = 1;


$stmtImagenLogin = $conexion->prepare(
    "SELECT
        imagen_login_admin,
        imagen_fondo_login_admin
     FROM configuracion_tienda
     WHERE id_configuracion = ?
     LIMIT 1"
);


if ($stmtImagenLogin) {

    $stmtImagenLogin->bind_param(
        "i",
        $idConfiguracion
    );

    $stmtImagenLogin->execute();

    $resultadoImagenLogin =
        $stmtImagenLogin->get_result();


    if (
        $filaImagenLogin =
        $resultadoImagenLogin->fetch_assoc()
    ) {

        // Imagen del banner izquierdo

        if (!empty($filaImagenLogin["imagen_login_admin"])) {

            $imagenLoginAdmin =
                "../" .
                $filaImagenLogin["imagen_login_admin"];
        }


        // Imagen de fondo del lado derecho

        if (!empty($filaImagenLogin["imagen_fondo_login_admin"])) {

            $imagenFondoLoginAdmin =
                "../" .
                $filaImagenLogin["imagen_fondo_login_admin"];
        }
    }


    $stmtImagenLogin->close();
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

    <title>Panel de Administración | AGRANDA</title>

    <link
        rel="stylesheet"
        href="admin.css"
    >

</head>


<body>


<div class="contenedor-login">


    <!-- ===============================
         LADO IZQUIERDO
    ================================ -->

    <section class="lado-izquierdo">


        <!-- LOGO / TÍTULO -->

        <div class="logo-admin">

            <h1>AGRANDA</h1>

            <h2>Panel de Administración</h2>

        </div>


        <!-- BANNER CONFIGURABLE -->

        <div
            class="banner-admin"

            <?php if ($imagenLoginAdmin !== ""): ?>

                style="
                    background-image:
                    url('<?= htmlspecialchars(
                        $imagenLoginAdmin,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>');
                "

            <?php endif; ?>
        >

            <?php if ($imagenLoginAdmin === ""): ?>

                <div class="banner-admin-vacio">

                    <span>AGRANDA</span>

                    <small>
                        Administración de la tienda
                    </small>

                </div>

            <?php endif; ?>

        </div>


        <!-- INFORMACIÓN -->

        <div class="informacion-admin">

            <p>✓ Gestión de productos</p>

            <p>✓ Control de inventario</p>

            <p>✓ Administración de pedidos</p>

            <p>✓ Gestión de clientes</p>

            <p>✓ Configuración de la tienda</p>

        </div>


    </section>


    <!-- ===============================
         LADO DERECHO
    ================================ -->

    <section
        class="lado-derecho"

        <?php if ($imagenFondoLoginAdmin !== ""): ?>

            style="
                background-image:
                    linear-gradient(
                        rgba(248,250,247,.82),
                        rgba(248,250,247,.82)
                    ),
                    url('<?= htmlspecialchars(
                        $imagenFondoLoginAdmin,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>');
            "

        <?php endif; ?>
    >


        <!-- FORMULARIO -->

        <div class="formulario-login">


            <div class="login-encabezado">

                <span class="login-etiqueta">
                    PANEL ADMINISTRATIVO
                </span>

                <h2>Bienvenido</h2>

                <p>
                    Ingrese sus credenciales para acceder
                    al panel de administración.
                </p>

            </div>


            <!-- MENSAJE ERROR -->

            <?php if (!empty($mensaje)): ?>

                <div class="mensaje-error">

                    <?php echo htmlspecialchars($mensaje); ?>

                </div>

            <?php endif; ?>


            <form
                action="validar_login.php"
                method="POST"
            >


                <div class="campo-login">

                    <label for="correo">
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        placeholder="Ingrese su correo electrónico"
                        value="<?php echo htmlspecialchars(
                            $_SESSION["correo"] ?? ""
                        ); ?>"
                        required
                    >

                </div>


                <div class="campo-login">

                    <label for="contrasena">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        id="contrasena"
                        name="contrasena"
                        placeholder="Ingrese su contraseña"
                        required
                    >

                </div>


                <button type="submit">
                    Iniciar sesión
                </button>


            </form>

        </div>

    </section>

</div>


<!-- ===============================
     PIE DE PÁGINA
================================ -->

<footer>

    <p>
        © 2026 AGRANDA | Todos los derechos reservados
    </p>

</footer>


<script src="admin.js"></script>


</body>

</html>