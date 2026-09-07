<?php

// ==========================================
// SESIÓN
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==========================================
// URL BASE
// ==========================================
$scriptDir =
    str_replace(
        "\\",
        "/",
        dirname($_SERVER["SCRIPT_NAME"])
    );

$posTienda =
    strpos(
        $scriptDir,
        "/tienda"
    );

if ($posTienda !== false) {

    $base_url =
        substr(
            $scriptDir,
            0,
            $posTienda + strlen("/tienda")
        ) . "/";

} else {

    $base_url = "/tienda/";
}


// ==========================================
// PROTEGER PÁGINA
// ==========================================
$clienteLogueado =
    isset($_SESSION["id_usuario"]) &&
    is_numeric($_SESSION["id_usuario"]) &&
    ($_SESSION["rol"] ?? "") === "cliente";

if (!$clienteLogueado) {

    header(
        "Location: " .
        $base_url .
        "cuenta/login.php"
    );

    exit;
}


// ==========================================
// BASE DE DATOS
// ==========================================
require_once __DIR__ . "/../../config/conexion.php";


// ==========================================
// ID DEL USUARIO
// ==========================================
$idUsuario =
    (int) $_SESSION["id_usuario"];


    // ==========================================
// TOKEN CSRF PARA FAVORITOS
// ==========================================
if (
    empty($_SESSION["csrf_favoritos"]) ||
    !is_string($_SESSION["csrf_favoritos"])
) {
    $_SESSION["csrf_favoritos"] =
        bin2hex(random_bytes(32));
}

$csrfFavoritos =
    $_SESSION["csrf_favoritos"];


// ==========================================
// CONSULTAR CLIENTE
// ==========================================
$sqlUsuario = "
    SELECT
        id_usuario,
        nombre,
        apellido,
        correo,
        telefono,
        fecha_registro
    FROM usuarios
    WHERE id_usuario = ?
      AND rol = 'cliente'
      AND estado = 1
    LIMIT 1
";

$stmtUsuario =
    $conexion->prepare($sqlUsuario);

if (!$stmtUsuario) {

    http_response_code(500);

    exit(
        "No fue posible cargar tu cuenta en este momento."
    );
}


$stmtUsuario->bind_param(
    "i",
    $idUsuario
);

$stmtUsuario->execute();

$resultadoUsuario =
    $stmtUsuario->get_result();

$datosUsuario =
    $resultadoUsuario->fetch_assoc();

$stmtUsuario->close();


// ==========================================
// VALIDAR QUE EL CLIENTE SIGA EXISTIENDO
// ==========================================
if (!$datosUsuario) {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $parametros =
            session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $parametros["path"],
            $parametros["domain"],
            $parametros["secure"],
            $parametros["httponly"]
        );
    }

    session_destroy();

    header(
        "Location: " .
        $base_url .
        "cuenta/login.php"
    );

    exit;
}


// ==========================================
// SINCRONIZAR SESIÓN
// ==========================================
$_SESSION["nombre"] =
    $datosUsuario["nombre"];

$_SESSION["apellido"] =
    $datosUsuario["apellido"];

$_SESSION["correo"] =
    $datosUsuario["correo"];

$_SESSION["rol"] =
    "cliente";


// ==========================================
// DATOS SEGUROS PARA MOSTRAR
// ==========================================
$nombreSeguro =
    htmlspecialchars(
        $datosUsuario["nombre"],
        ENT_QUOTES,
        "UTF-8"
    );

$apellidoSeguro =
    htmlspecialchars(
        $datosUsuario["apellido"],
        ENT_QUOTES,
        "UTF-8"
    );

$correoSeguro =
    htmlspecialchars(
        $datosUsuario["correo"],
        ENT_QUOTES,
        "UTF-8"
    );

$telefonoSeguro =
    !empty($datosUsuario["telefono"])
        ? htmlspecialchars(
            $datosUsuario["telefono"],
            ENT_QUOTES,
            "UTF-8"
        )
        : "No registrado";


// ==========================================
// FECHA DE REGISTRO
// ==========================================
$fechaRegistro = "No disponible";

if (!empty($datosUsuario["fecha_registro"])) {

    $timestamp =
        strtotime(
            $datosUsuario["fecha_registro"]
        );

    if ($timestamp !== false) {

        $fechaRegistro =
            date(
                "d/m/Y",
                $timestamp
            );
    }
}

// ==========================================
// FAVORITOS DEL CLIENTE
// ==========================================
    $favoritos = [];

    $sqlFavoritos = "
        SELECT
            f.id_favorito,
            f.id_producto,
            f.fecha_agregado,

            p.nombre,
            p.codigo_producto,
            p.precio,

            c.nombre AS categoria_nombre,
            m.nombre AS marca_nombre,

            i.stock_actual,

            img.ruta_imagen

        FROM favoritos f

        INNER JOIN productos p
            ON p.id_producto = f.id_producto

        INNER JOIN categorias c
            ON c.id_categoria = p.id_categoria

        LEFT JOIN marcas m
            ON m.id_marca = p.id_marca

        LEFT JOIN inventario i
            ON i.id_producto = p.id_producto

        LEFT JOIN imagenes_producto img
            ON img.id_producto = p.id_producto
            AND img.principal = 1
            AND img.estado = 1

        WHERE f.id_usuario = ?
        AND p.estado = 1

        ORDER BY f.fecha_agregado DESC
    ";


    $stmtFavoritos =
        $conexion->prepare($sqlFavoritos);


    if ($stmtFavoritos) {

        $stmtFavoritos->bind_param(
            "i",
            $idUsuario
        );

        $stmtFavoritos->execute();

        $resultadoFavoritos =
            $stmtFavoritos->get_result();


        while (
            $favorito =
                $resultadoFavoritos->fetch_assoc()
        ) {

            $favoritos[] =
                $favorito;
        }


        $stmtFavoritos->close();
    }


    // ==========================================
    // CONTADOR DE FAVORITOS
    // ==========================================
    $totalFavoritos =
        count($favoritos);

// ==========================================
// HEADER
// ==========================================
require_once __DIR__ . "/../includes/header.php";

?>

<main class="cuenta-page">

    <section class="cuenta-container">


        <!-- =================================
             ENCABEZADO DE LA CUENTA
        ================================== -->

        <div class="cuenta-page-encabezado">

            <div>

                <span class="cuenta-page-etiqueta">
                    ÁREA PERSONAL
                </span>

                <h1>
                    Mi cuenta
                </h1>

                <p>
                    Hola,
                    <strong>
                        <?= $nombreSeguro ?>
                    </strong>.
                    Administra tu información y actividad
                    en AGRANDA.
                </p>

            </div>


            <a
                href="<?= htmlspecialchars(
                    $base_url . "cuenta/logout.php",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                class="cerrar-sesion"
            >
                Cerrar sesión
            </a>

        </div>


        <!-- =================================
             CUERPO PRINCIPAL
        ================================== -->

        <div class="cuenta-dashboard">


            <!-- =============================
                 MENÚ IZQUIERDO
            ============================== -->

            <aside
                class="cuenta-menu"
                aria-label="Opciones de mi cuenta"
            >

                <p class="cuenta-menu-titulo">
                    Mi cuenta
                </p>


                <button
                    type="button"
                    class="cuenta-menu-opcion cuenta-menu-opcion-activa"
                    data-cuenta-vista="datos"
                    aria-selected="true"
                >
                    <span>
                        Mis datos
                    </span>
                </button>


                <button
                    type="button"
                    class="cuenta-menu-opcion"
                    data-cuenta-vista="pedidos"
                    aria-selected="false"
                >
                    <span>
                        Mis pedidos
                    </span>

                    <span class="cuenta-menu-contador">
                        0
                    </span>
                </button>


                <button
                    type="button"
                    class="cuenta-menu-opcion"
                    data-cuenta-vista="favoritos"
                    aria-selected="false"
                >
                    <span>
                        Favoritos
                    </span>

                    <span 
                        class="cuenta-menu-contador">                        
                        <?= $totalFavoritos ?>
                    </span>

                </button>


                <button
                    type="button"
                    class="cuenta-menu-opcion"
                    data-cuenta-vista="direcciones"
                    aria-selected="false"
                >
                    <span>
                        Direcciones
                    </span>
                </button>


                <button
                    type="button"
                    class="cuenta-menu-opcion"
                    data-cuenta-vista="cupones"
                    aria-selected="false"
                >
                    <span>
                        Cupones
                    </span>

                    <span class="cuenta-menu-contador">
                        0
                    </span>
                </button>

            </aside>


            <!-- =============================
                 CONTENIDO CENTRAL
            ============================== -->

            <section class="cuenta-contenido">


                <!-- =========================
                     MIS DATOS
                ========================== -->

                <div
                    class="cuenta-vista cuenta-vista-activa"
                    data-cuenta-contenido="datos"
                >

                    <div class="cuenta-vista-encabezado">

                        <div>

                            <span>
                                INFORMACIÓN PERSONAL
                            </span>

                            <h2>
                                Mis datos
                            </h2>

                            <p>
                                Información asociada a tu cuenta.
                            </p>

                        </div>

                    </div>


                    <div class="cuenta-datos-lista">


                        <div class="cuenta-dato">

                            <span>
                                Nombre
                            </span>

                            <strong>
                                <?= $nombreSeguro ?>
                            </strong>

                        </div>


                        <div class="cuenta-dato">

                            <span>
                                Apellido
                            </span>

                            <strong>
                                <?= $apellidoSeguro ?>
                            </strong>

                        </div>


                        <div class="cuenta-dato">

                            <span>
                                Correo electrónico
                            </span>

                            <strong>
                                <?= $correoSeguro ?>
                            </strong>

                        </div>


                        <div class="cuenta-dato">

                            <span>
                                Teléfono
                            </span>

                            <strong>
                                <?= $telefonoSeguro ?>
                            </strong>

                        </div>


                        <div class="cuenta-dato">

                            <span>
                                Miembro desde
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $fechaRegistro,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>

                        </div>

                    </div>


                    <div class="cuenta-vista-acciones">

                        <button
                            type="button"
                            class="cuenta-boton-secundario"
                            disabled
                        >
                            Editar datos
                        </button>

                    </div>

                </div>


                <!-- =========================
                     PEDIDOS
                ========================== -->

                <div
                    class="cuenta-vista"
                    data-cuenta-contenido="pedidos"
                    hidden
                >

                    <div class="cuenta-vista-encabezado">

                        <div>

                            <span>
                                COMPRAS
                            </span>

                            <h2>
                                Mis pedidos
                            </h2>

                            <p>
                                Consulta tus compras y su estado.
                            </p>

                        </div>

                    </div>


                    <div class="cuenta-estado-vacio">

                        <strong>
                            Aún no hay pedidos para mostrar.
                        </strong>

                        <p>
                            Cuando realices una compra,
                            aparecerá aquí.
                        </p>

                    </div>

                </div>


                <!-- =========================
                     FAVORITOS
                ========================== -->

                <div
                    class="cuenta-vista"
                    data-cuenta-contenido="favoritos"
                    hidden
                >

                    <div class="cuenta-vista-encabezado">

                        <div>

                            <span>
                                PRODUCTOS GUARDADOS
                            </span>

                            <h2>
                                Favoritos
                            </h2>

                            <p>
                                Tus productos guardados aparecerán aquí.
                            </p>

                        </div>

                    </div>


                    <?php if (empty($favoritos)): ?>

                    <div class="cuenta-estado-vacio">

                        <strong>
                            Aún no tienes favoritos.
                        </strong>

                        <p>
                            Guarda productos para encontrarlos
                            fácilmente después.
                        </p>

                        <a
                            href="<?= htmlspecialchars(
                                $base_url . "productos/",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            class="cuenta-favoritos-explorar"
                        >
                            Explorar productos
                        </a>

                    </div>


                <?php else: ?>

                    <div class="cuenta-favoritos-lista">

                        <?php foreach ($favoritos as $favorito): ?>

                            <article
                                class="cuenta-favorito-card"
                                data-favorito-card
                                data-producto-id="<?= (int) $favorito["id_producto"] ?>"
                            >


                                <!-- =========================
                                    IMAGEN
                                ========================== -->

                                <div class="cuenta-favorito-imagen">

                                    <?php if (!empty($favorito["ruta_imagen"])): ?>

                                        <img
                                            src="<?= htmlspecialchars(
                                                $base_url .
                                                "../" .
                                                $favorito["ruta_imagen"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            alt="<?= htmlspecialchars(
                                                $favorito["nombre"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            loading="lazy"
                                        >

                                    <?php else: ?>

                                        <div class="cuenta-favorito-sin-imagen">
                                            Sin imagen
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <!-- =========================
                                    INFORMACIÓN
                                ========================== -->

                                <div class="cuenta-favorito-info">

                                    <?php if (!empty($favorito["codigo_producto"])): ?>

                                        <span class="cuenta-favorito-codigo">

                                            <?= htmlspecialchars(
                                                $favorito["codigo_producto"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </span>

                                    <?php endif; ?>


                                    <h3>

                                        <?= htmlspecialchars(
                                            $favorito["nombre"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                    </h3>


                                    <div class="cuenta-favorito-meta">

                                        <?php if (!empty($favorito["categoria_nombre"])): ?>

                                            <span>

                                                <?= htmlspecialchars(
                                                    $favorito["categoria_nombre"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>

                                            </span>

                                        <?php endif; ?>


                                        <?php if (!empty($favorito["marca_nombre"])): ?>

                                            <span>

                                                <?= htmlspecialchars(
                                                    $favorito["marca_nombre"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <strong class="cuenta-favorito-precio">

                                        $<?= number_format(
                                            (float) $favorito["precio"],
                                            0,
                                            ",",
                                            "."
                                        ) ?>

                                    </strong>


                                    <div class="cuenta-favorito-stock">

                                        <?php if (
                                            isset($favorito["stock_actual"]) &&
                                            (int) $favorito["stock_actual"] > 0
                                        ): ?>

                                            <span class="favorito-stock-disponible">
                                                Disponible
                                            </span>

                                        <?php else: ?>

                                            <span class="favorito-stock-agotado">
                                                Agotado
                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <div class="cuenta-favorito-acciones">

                                        <a
                                            href="<?= htmlspecialchars(
                                                $base_url .
                                                "productos/?producto=" .
                                                (int) $favorito["id_producto"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            class="cuenta-favorito-boton"
                                        >
                                            Ver producto
                                        </a>


                                        <button
                                            type="button"
                                            class="cuenta-favorito-quitar"
                                            data-quitar-favorito
                                            data-producto-id="<?= (int) $favorito["id_producto"] ?>"
                                            data-csrf="<?= htmlspecialchars(
                                                $csrfFavoritos,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >
                                            Quitar
                                        </button>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <!-- =========================
                     DIRECCIONES
                ========================== -->

                <div
                    class="cuenta-vista"
                    data-cuenta-contenido="direcciones"
                    hidden
                >

                    <div class="cuenta-vista-encabezado">

                        <div>

                            <span>
                                ENVÍOS
                            </span>

                            <h2>
                                Direcciones
                            </h2>

                            <p>
                                Administra tus direcciones de entrega.
                            </p>

                        </div>

                    </div>


                    <div class="cuenta-estado-vacio">

                        <strong>
                            No hay direcciones registradas.
                        </strong>

                        <p>
                            Más adelante podrás agregarlas desde aquí.
                        </p>

                    </div>

                </div>


                <!-- =========================
                     CUPONES
                ========================== -->

                <div
                    class="cuenta-vista"
                    data-cuenta-contenido="cupones"
                    hidden
                >

                    <div class="cuenta-vista-encabezado">

                        <div>

                            <span>
                                BENEFICIOS
                            </span>

                            <h2>
                                Cupones
                            </h2>

                            <p>
                                Consulta los beneficios disponibles
                                para tu cuenta.
                            </p>

                        </div>

                    </div>


                    <div class="cuenta-estado-vacio">

                        <strong>
                            No tienes cupones disponibles.
                        </strong>

                    </div>

                </div>

            </section>


            <!-- =============================
                 IMAGEN DERECHA
            ============================== -->

            <aside class="cuenta-banner">

                <div class="cuenta-banner-contenido">

                    <span>
                        AGRANDA
                    </span>

                    <h2>
                        Todo para mantener
                        tu maquinaria en marcha.
                    </h2>

                    <p>
                        Repuestos agrícolas
                        de confianza.
                    </p>

                </div>

            </aside>

        </div>

    </section>

</main>


<script
    src="<?= htmlspecialchars(
        $base_url . "js/cuenta.js",
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
    defer
></script>


<?php

require_once __DIR__ . "/../includes/footer.php";

?>