<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");


// =================================
// TOKEN CSRF
// =================================

if (empty($_SESSION["csrf_apariencia"])) {
    $_SESSION["csrf_apariencia"] = bin2hex(random_bytes(32));
}


// =================================
// OBTENER CONFIGURACIÓN
// =================================

$stmt = $conexion->prepare(
    "SELECT
        id_configuracion,
        nombre_tienda,
        logo,
        imagen_hero,
        imagen_login_admin,
        imagen_fondo_login_admin,
        imagen_dashboard_admin
     FROM configuracion_tienda
     WHERE id_configuracion = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Error al preparar la consulta.");
}

$idConfiguracion = 1;

$stmt->bind_param("i", $idConfiguracion);

$stmt->execute();

$resultado = $stmt->get_result();

$configuracion = $resultado->fetch_assoc();

$stmt->close();


// =================================
// VALIDAR QUE EXISTA CONFIGURACIÓN
// =================================

if (!$configuracion) {
    die("No se encontró la configuración de la tienda.");
}


// =================================
// MENSAJES
// =================================

$mensajeExito = "";
$mensajeError = "";

if (isset($_GET["actualizado"]) && $_GET["actualizado"] === "1") {

    $mensajeExito =
        "La apariencia de AGRANDA se actualizó correctamente.";
}


$error = $_GET["error"] ?? "";


$errores = [

    "csrf" =>
        "La sesión del formulario expiró. Intenta nuevamente.",

    "carpeta" =>
        "No fue posible preparar la carpeta para guardar las imágenes.",

    "consulta" =>
        "No fue posible consultar la configuración.",

    "configuracion" =>
        "No se encontró la configuración de AGRANDA.",

    "subida" =>
        "Ocurrió un error al subir una de las imágenes.",

    "tamano" =>
        "La imagen supera el tamaño máximo permitido de 2 MB.",

    "archivo" =>
        "El archivo recibido no es válido.",

    "formato" =>
        "Solo se permiten imágenes JPG, PNG o WEBP.",

    "guardar_archivo" =>
        "No fue posible guardar la imagen en el servidor.",

    "base_datos" =>
        "No fue posible guardar los cambios en la base de datos."

];


if (
    $error !== "" &&
    isset($errores[$error])
) {

    $mensajeError = $errores[$error];
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Apariencia | AGRANDA</title>

    <link rel="stylesheet" href="configuracion.css">

</head>


<body>

<div class="contenedor-dashboard">


    <!-- ===============================
         MENÚ LATERAL
    ================================ -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Configuración</p>

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

                <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                <li>
                    <a href="configuracion.php" class="activo">
                        ⚙️ Configuración
                    </a>
                </li>

            </ul>

        </nav>

    </aside>


    <!-- ===============================
         CONTENIDO PRINCIPAL
    ================================ -->

    <main class="contenido">


        <!-- ===============================
             ENCABEZADO
        ================================ -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>
                    ¡Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"]); ?>!
                </h2>

                <p>Panel de Administración AGRANDA</p>

            </div>


            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>

                <div class="usuario">

                    👤 <?php echo htmlspecialchars($_SESSION["nombre"]); ?>

                </div>

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>


        <!-- ===============================
             APARIENCIA
        ================================ -->

        <section class="contenido-configuracion">


            <!-- ENCABEZADO DE SECCIÓN -->

            <div class="encabezado-subconfiguracion">

                <div>

                    <span class="subconfiguracion-etiqueta">
                        CONFIGURACIÓN VISUAL
                    </span>

                    <h2>🎨 Apariencia</h2>

                    <p>
                        Administra las imágenes principales utilizadas
                        en la tienda y en el panel administrativo.
                    </p>

                </div>


                <a href="configuracion.php" class="btn-volver-configuracion">
                    ← Volver a configuración
                </a>

            </div>


            <!-- MENSAJE DE ÉXITO -->

            <?php if ($mensajeExito !== ""): ?>

                <div class="mensaje-configuracion-exito">

                    <?php echo htmlspecialchars($mensajeExito); ?>

                </div>

            <?php endif; ?>

            <?php if ($mensajeError !== ""): ?>

                <div class="mensaje-configuracion-error">

                    <?php echo htmlspecialchars($mensajeError); ?>

                </div>

            <?php endif; ?>

            <!-- ===============================
                 FORMULARIO
            ================================ -->

            <form
                action="guardar_apariencia.php"
                method="POST"
                enctype="multipart/form-data"
                class="formulario-apariencia"
            >

                <input
                    type="hidden"
                    name="csrf"
                    value="<?php echo htmlspecialchars($_SESSION["csrf_apariencia"]); ?>"
                >


                <!-- ===============================
                     LOGO
                ================================ -->

                <div class="apariencia-card">

                    <div class="apariencia-informacion">

                        <span class="apariencia-numero">01</span>

                        <h3>Logo de la tienda</h3>

                        <p>
                            Imagen utilizada junto al nombre AGRANDA
                            en el encabezado de la tienda pública.
                        </p>

                        <span class="apariencia-recomendacion">
                            Formatos permitidos: JPG, PNG o WEBP.
                        </span>

                    </div>


                    <div class="apariencia-control">

                        <div class="apariencia-preview">

                            <?php if (!empty($configuracion["logo"])): ?>

                                <img
                                    src="../../<?php echo htmlspecialchars($configuracion["logo"]); ?>"
                                    alt="Logo actual de AGRANDA"
                                >

                            <?php else: ?>

                                <div class="apariencia-sin-imagen">

                                    <span>AGRANDA</span>

                                    <small>Sin logo configurado</small>

                                </div>

                            <?php endif; ?>

                        </div>


                        <label class="selector-imagen">

                            <span>Seleccionar logo</span>

                            <input
                                type="file"
                                name="logo"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                        </label>

                    </div>

                </div>


                <!-- ===============================
                     HERO DE INICIO
                ================================ -->

                <div class="apariencia-card">

                    <div class="apariencia-informacion">

                        <span class="apariencia-numero">02</span>

                        <h3>Imagen principal de Inicio</h3>

                        <p>
                            Imagen que aparecerá en el banner principal
                            de la página de Inicio de AGRANDA.
                        </p>

                        <span class="apariencia-recomendacion">
                            Se recomienda una imagen horizontal de buena calidad.
                        </span>

                    </div>


                    <div class="apariencia-control">

                        <div class="apariencia-preview apariencia-preview-horizontal">

                            <?php if (!empty($configuracion["imagen_hero"])): ?>

                                <img
                                    src="../../<?php echo htmlspecialchars($configuracion["imagen_hero"]); ?>"
                                    alt="Imagen principal actual"
                                >

                            <?php else: ?>

                                <img
                                    src="../../tienda/assets/tractor-banner.jpg"
                                    alt="Imagen principal predeterminada"
                                >

                            <?php endif; ?>

                        </div>


                        <label class="selector-imagen">

                            <span>Seleccionar imagen</span>

                            <input
                                type="file"
                                name="imagen_hero"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                        </label>

                    </div>

                </div>


                <!-- ===============================
                     LOGIN ADMIN
                ================================ -->

                <div class="apariencia-card">

                    <div class="apariencia-informacion">

                        <span class="apariencia-numero">03</span>

                        <h3>Imagen del login administrativo</h3>

                        <p>
                            Imagen que aparecerá en el costado izquierdo
                            de la pantalla de acceso al panel administrativo.
                        </p>

                        <span class="apariencia-recomendacion">
                            La imagen se adaptará automáticamente al contenedor.
                        </span>

                    </div>


                    <div class="apariencia-control">

                        <div class="apariencia-preview apariencia-preview-horizontal">

                            <?php if (!empty($configuracion["imagen_login_admin"])): ?>

                                <img
                                    src="../../<?php echo htmlspecialchars($configuracion["imagen_login_admin"]); ?>"
                                    alt="Imagen actual del login administrativo"
                                >

                            <?php else: ?>

                                <div class="apariencia-sin-imagen">

                                    <span>Panel administrativo</span>

                                    <small>Sin imagen configurada</small>

                                </div>

                            <?php endif; ?>

                        </div>


                        <label class="selector-imagen">

                            <span>Seleccionar imagen</span>

                            <input
                                type="file"
                                name="imagen_login_admin"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                        </label>

                    </div>

                </div>

                <!-- ===============================
                    FONDO LOGIN ADMIN
                ================================ -->

                <div class="apariencia-card">

                    <div class="apariencia-informacion">

                        <span class="apariencia-numero">04</span>

                        <h3>Fondo del login administrativo</h3>

                        <p>
                            Imagen de fondo que aparecerá detrás del formulario
                            de acceso al panel administrativo.
                        </p>

                        <span class="apariencia-recomendacion">
                            Se recomienda una imagen horizontal y sin demasiado texto.
                        </span>

                    </div>


                    <div class="apariencia-control">

                        <div class="apariencia-preview apariencia-preview-horizontal">

                            <?php if (!empty($configuracion["imagen_fondo_login_admin"])): ?>

                                <img
                                    src="../../<?php echo htmlspecialchars(
                                        $configuracion["imagen_fondo_login_admin"]
                                    ); ?>"
                                    alt="Fondo actual del login administrativo"
                                >

                            <?php else: ?>

                                <div class="apariencia-sin-imagen">

                                    <span>Fondo del login</span>

                                    <small>Sin imagen configurada</small>

                                </div>

                            <?php endif; ?>

                        </div>


                        <label class="selector-imagen">

                            <span>Seleccionar imagen</span>

                            <input
                                type="file"
                                name="imagen_fondo_login_admin"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                        </label>

                    </div>

                </div>


                <!-- ===============================
                     DASHBOARD ADMIN
                ================================ -->

                <div class="apariencia-card">

                    <div class="apariencia-informacion">

                        <span class="apariencia-numero">05</span>

                        <h3>Imagen del Dashboard</h3>

                        <p>
                            Imagen decorativa que se utilizará en el bloque
                            principal del Dashboard administrativo.
                        </p>

                        <span class="apariencia-recomendacion">
                            Se recomienda una imagen horizontal.
                        </span>

                    </div>


                    <div class="apariencia-control">

                        <div class="apariencia-preview apariencia-preview-horizontal">

                            <?php if (!empty($configuracion["imagen_dashboard_admin"])): ?>

                                <img
                                    src="../../<?php echo htmlspecialchars($configuracion["imagen_dashboard_admin"]); ?>"
                                    alt="Imagen actual del Dashboard"
                                >

                            <?php else: ?>

                                <div class="apariencia-sin-imagen">

                                    <span>Dashboard AGRANDA</span>

                                    <small>Sin imagen configurada</small>

                                </div>

                            <?php endif; ?>

                        </div>


                        <label class="selector-imagen">

                            <span>Seleccionar imagen</span>

                            <input
                                type="file"
                                name="imagen_dashboard_admin"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                        </label>

                    </div>

                </div>


                <!-- ===============================
                     ACCIONES
                ================================ -->

                <div class="acciones-apariencia">

                    <a
                        href="configuracion.php"
                        class="btn-cancelar-configuracion"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn-guardar-configuracion"
                    >
                        Guardar cambios
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>


<script src="../dashboard/dashboard.js"></script>

</body>

</html>