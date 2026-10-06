<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
if (!agranda_admin_autorizado()) { header('Location: ../login.php'); exit(); }
require_once __DIR__ . '/../../config/conexion.php';
if (empty($_SESSION['csrf_config_nosotros']) || !is_string($_SESSION['csrf_config_nosotros'])) { $_SESSION['csrf_config_nosotros'] = bin2hex(random_bytes(32)); }
$campos = ['nosotros_titulo'=>['Título',150], 'nosotros_descripcion'=>['Quiénes somos',5000], 'nosotros_mision'=>['Misión',2000], 'nosotros_vision'=>['Visión',2000], 'nosotros_valores'=>['Valores',1209]];
$contenido = array_fill_keys(array_keys($campos), '');
$mensajeError = $_SESSION['config_nosotros_error'] ?? '';
$mensajeExito = $_SESSION['config_nosotros_exito'] ?? '';
$anteriores = $_SESSION['config_nosotros_valores'] ?? null;
unset($_SESSION['config_nosotros_error'], $_SESSION['config_nosotros_exito'], $_SESSION['config_nosotros_valores']);
$configuracionDisponible = false;
$imagenes = ['nosotros_imagen_hero'=>'Imagen principal del hero', 'nosotros_imagen_institucional'=>'Imagen institucional'];
$imagenesActuales = [];

try {
    $id = 1;
    $stmt = $conexion->prepare('SELECT nosotros_titulo, nosotros_descripcion, nosotros_mision, nosotros_vision, nosotros_valores, nosotros_imagen_hero, nosotros_imagen_institucional FROM configuracion_tienda WHERE id_configuracion = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute(); $fila = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$fila) { throw new RuntimeException('Configuración ausente'); }
    foreach ($contenido as $campo => $valor) { $contenido[$campo] = (string) ($fila[$campo] ?? ''); }
    foreach ($imagenes as $campo => $etiqueta) {
        $ruta = $fila[$campo] ?? '';
        if (is_string($ruta) && preg_match('#^uploads/tienda/nosotros_(hero|institucional)_[a-f0-9]{32}\.(jpg|png|webp)$#D', $ruta)) {
            $archivo = __DIR__ . '/../../' . $ruta;
            if (is_file($archivo) && !is_link($archivo) && dirname(realpath($archivo)) === realpath(__DIR__ . '/../../uploads/tienda')) { $imagenesActuales[$campo] = $ruta; }
        }
    }
    $configuracionDisponible = true;
} catch (Throwable $error) { $mensajeError = 'No fue posible cargar la configuración de Nosotros.'; }
if (is_array($anteriores)) { foreach ($contenido as $campo => $valor) { if (isset($anteriores[$campo]) && is_string($anteriores[$campo])) { $contenido[$campo] = $anteriores[$campo]; } } }
?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nosotros | AGRANDA</title>

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
    <div class="encabezado-subconfiguracion"><div><span class="subconfiguracion-etiqueta">INFORMACIÓN INSTITUCIONAL</span><h2>Nosotros</h2><p>Administra el contenido de Nosotros que se muestra en la tienda.</p></div><a href="configuracion.php" class="btn-volver-configuracion">← Volver a configuración</a></div>
    <?php if ($mensajeExito !== ''): ?><div class="mensaje-configuracion-exito" role="status"><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($mensajeError !== ''): ?><div class="mensaje-configuracion-error" role="alert"><?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form action="guardar_nosotros.php" method="POST" enctype="multipart/form-data" class="formulario-apariencia formulario-nosotros">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_config_nosotros'], ENT_QUOTES, 'UTF-8') ?>">
        <?php foreach ($campos as $campo => [$etiqueta, $limite]): ?>
        <div class="apariencia-card"><div class="apariencia-informacion"><h3><?= $etiqueta ?></h3><p id="ayuda-<?= $campo ?>"><?= $campo === 'nosotros_valores' ? 'Opcional: un valor por línea, máximo 10 valores de 120 caracteres.' : 'Texto plano, sin HTML. Máximo ' . $limite . ' caracteres.' ?></p></div><div class="apariencia-control">
            <label for="<?= $campo ?>"><?= $etiqueta ?></label>
            <?php if ($campo === 'nosotros_titulo'): ?>
            <input type="text" id="<?= $campo ?>" name="<?= $campo ?>" maxlength="<?= $limite ?>" required aria-describedby="ayuda-<?= $campo ?>" value="<?= htmlspecialchars($contenido[$campo], ENT_QUOTES, 'UTF-8') ?>">
            <?php else: ?>
            <textarea id="<?= $campo ?>" name="<?= $campo ?>" rows="<?= $campo === 'nosotros_valores' ? 6 : 5 ?>" maxlength="<?= $limite ?>" <?= $campo === 'nosotros_valores' ? '' : 'required' ?> aria-describedby="ayuda-<?= $campo ?>"><?= htmlspecialchars($contenido[$campo], ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php endif; ?>
        </div></div>
        <?php endforeach; ?>
        <?php foreach ($imagenes as $campo => $etiqueta): ?>
        <div class="apariencia-card"><div class="apariencia-informacion"><h3><?= $etiqueta ?></h3><p id="ayuda-<?= $campo ?>">JPG/JPEG, PNG o WEBP. Máximo 2 MB; 8000 px por lado y 20 megapíxeles. No selecciones un archivo si deseas conservar la imagen actual.</p></div><div class="apariencia-control">
            <?php if (isset($imagenesActuales[$campo])): ?>
            <img class="nosotros-preview" src="<?= htmlspecialchars('../../' . $imagenesActuales[$campo], ENT_QUOTES, 'UTF-8') ?>" alt="<?= $etiqueta ?> actual">
            <?php else: ?><p>Sin imagen configurada.</p><?php endif; ?>
            <label for="<?= $campo ?>"><?= $etiqueta ?></label><input type="file" id="<?= $campo ?>" name="<?= $campo ?>" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="ayuda-<?= $campo ?>">
            <?php if (!empty($fila[$campo])): ?><label class="nosotros-eliminar"><input type="checkbox" name="eliminar_<?= $campo ?>" value="1"> Eliminar imagen actual</label><?php endif; ?>
        </div></div>
        <?php endforeach; ?>
        <div class="acciones-apariencia"><a href="configuracion.php" class="btn-cancelar-configuracion">Cancelar</a><button type="submit" class="btn-guardar-configuracion" <?= $configuracionDisponible ? '' : 'disabled' ?>>Guardar Nosotros</button></div>
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
