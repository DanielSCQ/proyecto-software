<?php

session_start();
require_once __DIR__ . "/../includes/auth.php";

if (!agranda_admin_autorizado()) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . "/../../config/conexion.php";

if (empty($_SESSION["csrf_config_contacto"]) || !is_string($_SESSION["csrf_config_contacto"])) {
    $_SESSION["csrf_config_contacto"] = bin2hex(random_bytes(32));
}

$idConfiguracion = 1;
$telefonoContacto = "";
$mensajeError = $_SESSION["config_contacto_error"] ?? "";
$mensajeExito = $_SESSION["config_contacto_exito"] ?? "";
$valorAnterior = $_SESSION["config_contacto_valor"] ?? null;
unset($_SESSION["config_contacto_error"], $_SESSION["config_contacto_exito"], $_SESSION["config_contacto_valor"]);
$configuracionDisponible = false;

try {
    $stmt = $conexion->prepare("SELECT telefono_contacto FROM configuracion_tienda WHERE id_configuracion = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException("Consulta no disponible.");
    }
    $stmt->bind_param("i", $idConfiguracion);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($fila) {
        $configuracionDisponible = true;
        $telefonoContacto = (string) ($fila["telefono_contacto"] ?? "");
    } else {
        $mensajeError = "No se encontró la configuración de la tienda.";
    }
} catch (Throwable $error) {
    $mensajeError = "No fue posible cargar el teléfono de contacto. Inténtalo nuevamente.";
}

if (is_string($valorAnterior)) {
    $telefonoContacto = $valorAnterior;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contacto | AGRANDA</title>

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars(
            v_admin("configuracion.css", __DIR__),
            ENT_QUOTES,
            "UTF-8"
        ) ?>"
    >

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

                <li><a href="configuracion.php" class="activo">⚙️ Configuración</a></li>

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

                <h2>¡Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"] ?? "", ENT_QUOTES, "UTF-8"); ?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>


            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>

                <div class="usuario">
                    👤 <?php echo htmlspecialchars($_SESSION["nombre"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                </div>

                <a href="../cerrar_sesion.php" class="btn-salir">
                    Cerrar sesión
                </a>

            </div>

        </header>


        <!-- ===============================
             CONFIGURACIÓN
        ================================ -->

        <section class="contenido-configuracion">
    <div class="encabezado-subconfiguracion">
        <div>
            <span class="subconfiguracion-etiqueta">ATENCIÓN AL CLIENTE</span>
            <h2>Contacto</h2>
            <p>Configura el celular de atención por WhatsApp que se utilizará en Contacto.</p>
        </div>
        <a href="configuracion.php" class="btn-volver-configuracion">← Volver a configuración</a>
    </div>

    <?php if ($mensajeExito !== ""): ?>
        <div class="mensaje-configuracion-exito" role="status"><?= htmlspecialchars($mensajeExito, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <?php if ($mensajeError !== ""): ?>
        <div class="mensaje-configuracion-error" role="alert"><?= htmlspecialchars($mensajeError, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <form action="guardar_contacto.php" method="POST" class="formulario-apariencia">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION["csrf_config_contacto"], ENT_QUOTES, "UTF-8") ?>">
        <div class="apariencia-card">
            <div class="apariencia-informacion">
                <h3>Teléfono de WhatsApp</h3>
                <p>Usa un celular colombiano de 10 dígitos que comience por 3, con o sin el código +57. Puedes escribir espacios, guiones y paréntesis.</p>
                <small class="apariencia-recomendacion" id="ayuda-telefono">Déjalo vacío para desactivar WhatsApp. El número no se mostrará como texto en la tienda, pero estará incluido en el enlace de conversación.</small>
            </div>
            <div class="apariencia-control">
                <label for="telefono_contacto">Celular de atención</label>
                <input type="tel" id="telefono_contacto" name="telefono_contacto" maxlength="40" autocomplete="off" aria-describedby="ayuda-telefono" value="<?= htmlspecialchars($telefonoContacto, ENT_QUOTES, "UTF-8") ?>" style="width:100%;padding:12px 14px;border:1px solid #cfd8d0;border-radius:8px;font:inherit;">
            </div>
        </div>
        <div class="acciones-apariencia">
            <a href="configuracion.php" class="btn-cancelar-configuracion">Cancelar</a>
            <button type="submit" class="btn-guardar-configuracion" <?= $configuracionDisponible ? "" : "disabled" ?>>Guardar contacto</button>
        </div>
    </form>
</section>

    </main>

</div>


<script
    src="<?= htmlspecialchars(
        v_admin("../dashboard/dashboard.js", __DIR__),
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
></script>

</body>

</html>