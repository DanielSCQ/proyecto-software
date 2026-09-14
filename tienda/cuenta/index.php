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
    // TOKEN CSRF PARA CARRITO
    // ==========================================
    if (
        empty($_SESSION["csrf_carrito"]) ||
        !is_string($_SESSION["csrf_carrito"])
    ) {
        $_SESSION["csrf_carrito"] =
            bin2hex(random_bytes(32));
    }

    $csrfCarrito =
        $_SESSION["csrf_carrito"];

    // ==========================================
    // TOKEN CSRF PARA EDITAR DATOS
    // ==========================================
    if (
        empty($_SESSION["csrf_editar_datos"]) ||
        !is_string($_SESSION["csrf_editar_datos"])
    ) {
        $_SESSION["csrf_editar_datos"] =
            bin2hex(random_bytes(32));
    }

    $csrfEditarDatos =
        $_SESSION["csrf_editar_datos"];


    // ==========================================
    // TOKEN CSRF PARA DIRECCIONES
    // ==========================================
    if (
        empty($_SESSION["csrf_direcciones"]) ||
        !is_string($_SESSION["csrf_direcciones"])
    ) {
        $_SESSION["csrf_direcciones"] =
            bin2hex(random_bytes(32));
    }

    $csrfDirecciones =
        $_SESSION["csrf_direcciones"];

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
    // PEDIDOS DEL CLIENTE
    // ==========================================

    $pedidos = [];

    $sqlPedidos = "
        SELECT
            p.id_pedido,
            p.total,
            p.metodo_pago,
            ep.id_estado,
            ep.nombre AS estado

        FROM pedidos p

        INNER JOIN estado_pedido ep
            ON ep.id_estado = p.id_estado

        WHERE p.id_usuario = ?

        ORDER BY p.id_pedido DESC
    ";


    $stmtPedidos =
        $conexion->prepare(
            $sqlPedidos
        );


    if ($stmtPedidos) {

        $stmtPedidos->bind_param(
            "i",
            $idUsuario
        );

        $stmtPedidos->execute();

        $resultadoPedidos =
            $stmtPedidos->get_result();


        while (
            $pedido =
                $resultadoPedidos->fetch_assoc()
        ) {

            $pedidos[] =
                $pedido;
        }


        $stmtPedidos->close();
    }


    // ==========================================
    // CONTADOR DE PEDIDOS
    // ==========================================

    $totalPedidos =
        count($pedidos);    


    // ==========================================
    // DIRECCIONES DEL CLIENTE
    // ==========================================

    $direcciones = [];

    $sqlDirecciones = "
        SELECT
            id_direccion,
            nombre,
            telefono,
            receptor,
            direccion,
            barrio,
            municipio,
            departamento,
            referencia,
            principal
        FROM direcciones
        WHERE id_usuario = ?
        AND estado = 1
        ORDER BY
            principal DESC,
            id_direccion DESC
    ";


    $stmtDirecciones =
        $conexion->prepare(
            $sqlDirecciones
        );


    if ($stmtDirecciones) {

        $stmtDirecciones->bind_param(
            "i",
            $idUsuario
        );

        $stmtDirecciones->execute();

        $resultadoDirecciones =
            $stmtDirecciones->get_result();


        while (
            $direccionCliente =
                $resultadoDirecciones->fetch_assoc()
        ) {

            $direcciones[] =
                $direccionCliente;
        }


        $stmtDirecciones->close();
    }


    $totalDirecciones =
        count($direcciones);


    // =========================================
    // MENSAJES DE DIRECCIONES
    // =========================================

    $direccionExito =
        $_SESSION["direccion_exito"] ?? "";

    $direccionError =
        $_SESSION["direccion_error"] ?? "";

    $direccionErrores =
        $_SESSION["direccion_errores"] ?? [];

    $direccionForm =
        $_SESSION["direccion_form"] ?? [];

    unset(
        $_SESSION["direccion_exito"],
        $_SESSION["direccion_error"],
        $_SESSION["direccion_errores"],
        $_SESSION["direccion_form"]
    );


    // =========================================
    // VALORES DEL FORMULARIO
    // =========================================

    $dirNombre =
        $direccionForm["nombre"] ?? "";

    $dirReceptor =
        $direccionForm["receptor"] ?? "";

    $dirTelefono =
        $direccionForm["telefono"] ?? "";

    $dirDireccion =
        $direccionForm["direccion"] ?? "";

    $dirBarrio =
        $direccionForm["barrio"] ?? "";

    $dirMunicipio =
        $direccionForm["municipio"] ?? "";

    $dirDepartamento =
        $direccionForm["departamento"] ?? "";

    $dirReferencia =
        $direccionForm["referencia"] ?? "";

    $dirPrincipal =
        !empty(
            $direccionForm["principal"]
        );

// =========================================
// MODO DEL FORMULARIO DE DIRECCIÓN
// =========================================

$modoDireccion =
    $direccionForm["modo"] ?? "agregar";

$idDireccionEditar =
    isset($direccionForm["id_direccion"])
        ? (int) $direccionForm["id_direccion"]
        : 0;

$editandoDireccion =
    $modoDireccion === "editar" &&
    $idDireccionEditar > 0;


// ==========================================
// HEADER
// ==========================================
require_once __DIR__ . "/../includes/header.php";

?>

<link
    rel="stylesheet"
    href="<?= htmlspecialchars(
        $base_url . "css/productos.css",
        ENT_QUOTES,
        "UTF-8"
    ) ?>"
>

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
                        <?= $totalPedidos ?>
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

                    <span class="cuenta-menu-contador">
                        <?= $totalDirecciones ?>
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
                            id="btnEditarDatos"
                        >
                            Editar datos
                        </button>

                    </div>

                    <form
                        action="actualizar_datos.php"
                        method="POST"
                        class="cuenta-formulario-edicion"
                        id="formEditarDatos"
                        hidden
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                $csrfEditarDatos,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                        >


                        <div class="campo">

                            <label for="editarNombre">
                                Nombre
                            </label>

                            <input
                                type="text"
                                id="editarNombre"
                                name="nombre"
                                value="<?= $nombreSeguro ?>"
                                maxlength="50"
                                required
                                autocomplete="given-name"
                                data-contador
                            >

                            <div class="contador">
                                <span class="contador-actual">
                                    <?= mb_strlen(
                                        $datosUsuario["nombre"],
                                        "UTF-8"
                                    ) ?>
                                </span>/100
                            </div>

                        </div>


                        <div class="campo">

                            <label for="editarApellido">
                                Apellido
                            </label>

                            <input
                                type="text"
                                id="editarApellido"
                                name="apellido"
                                value="<?= $apellidoSeguro ?>"
                                maxlength="50"
                                required
                                autocomplete="family-name"
                                data-contador
                            >

                            <div class="contador">
                                <span class="contador-actual">
                                    <?= mb_strlen(
                                        $datosUsuario["apellido"],
                                        "UTF-8"
                                    ) ?>
                                </span>/100
                            </div>

                        </div>


                        <div class="campo">

                            <label for="editarTelefono">
                                Teléfono
                            </label>

                            <input
                                type="tel"
                                id="editarTelefono"
                                name="telefono"
                                value="<?= htmlspecialchars(
                                    $datosUsuario["telefono"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                maxlength="20"
                                autocomplete="tel"
                                data-contador
                            >

                            <div class="contador">
                                <span class="contador-actual">
                                    <?= mb_strlen(
                                        $datosUsuario["telefono"] ?? "",
                                        "UTF-8"
                                    ) ?>
                                </span>/20
                            </div>

                        </div>


                        <div class="campo campo-bloqueado">

                            <label>
                                Correo electrónico
                            </label>

                            <button
                                type="button"
                                class="cuenta-campo-bloqueado"
                                id="btnCorreoBloqueado"
                            >
                                <span>
                                    <?= $correoSeguro ?>
                                </span>

                                <small>
                                    No editable
                                </small>
                            </button>

                        </div>


                        <div class="campo campo-bloqueado">

                            <label>
                                Miembro desde
                            </label>

                            <div class="cuenta-campo-informativo">
                                <?= htmlspecialchars(
                                    $fechaRegistro,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </div>

                        </div>


                        <div
                            class="cuenta-aviso-correo"
                            id="avisoCorreo"
                            hidden
                        >

                            <strong>
                                Este dato requiere atención especial.
                            </strong>

                            <p>
                                Por seguridad, el correo electrónico
                                no puede modificarse directamente
                                desde tu cuenta.
                            </p>

                            <p>
                                Más adelante podrás solicitar el cambio
                                mediante nuestra sección de Contacto.
                            </p>

                        </div>


                        <div class="cuenta-formulario-acciones">

                            <button
                                type="submit"
                                class="cuenta-boton-primario"
                                id="btnGuardarDatos"
                            >
                                Guardar cambios
                            </button>

                            <button
                                type="button"
                                class="cuenta-boton-secundario"
                                id="btnCancelarEdicion"
                            >
                                Cancelar
                            </button>

                        </div>

                    </form>

                    
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


                    <?php if (empty($pedidos)): ?>

                        <div class="cuenta-estado-vacio">

                            <strong>
                                Aún no hay pedidos para mostrar.
                            </strong>

                            <p>
                                Cuando realices una compra,
                                aparecerá aquí.
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

                        <div class="cuenta-pedidos-lista">

                            <?php foreach ($pedidos as $pedido): ?>

                                <article class="cuenta-pedido-card">

                                    <div class="cuenta-pedido-superior">

                                        <div>

                                            <span class="cuenta-pedido-numero">
                                                Pedido
                                            </span>

                                            <h3>
                                                #<?= (int) $pedido["id_pedido"] ?>
                                            </h3>

                                        </div>


                                        <span
                                            class="
                                                cuenta-pedido-estado
                                                cuenta-pedido-estado-<?=
                                                    (int) $pedido["id_estado"]
                                                ?>
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $pedido["estado"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </span>
                                        
                                    </div>


                                    <div class="cuenta-pedido-datos">

                                        <div>

                                            <span>
                                                Método de pago
                                            </span>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $pedido["metodo_pago"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Total
                                            </span>

                                            <strong>
                                                $<?= number_format(
                                                    (float) $pedido["total"],
                                                    0,
                                                    ",",
                                                    "."
                                                ) ?>
                                            </strong>

                                        </div>

                                    </div>

                                    <div class="cuenta-pedido-acciones">

                                        <a
                                            href="<?= htmlspecialchars(
                                                $base_url .
                                                "pedidos/detalle.php?id=" .
                                                (int) $pedido["id_pedido"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            class="cuenta-boton-secundario"
                                        >
                                            Ver detalle
                                        </a>

                                    </div>
                                    
                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

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

                                        <button
                                            type="button"
                                            class="cuenta-favorito-boton"
                                            data-ver-producto
                                            data-producto-id="<?= (int) $favorito["id_producto"] ?>"
                                        >
                                            Ver producto
                                        </button>

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

                </div>

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

                    <?php if (empty($direcciones)): ?>

                        <div class="cuenta-estado-vacio">

                            <strong>
                                No tienes direcciones registradas.
                            </strong>

                            <p>
                                Agrega una dirección para utilizarla
                                en tus próximas compras.
                            </p>

                            <button
                                type="button"
                                class="cuenta-boton-primario"
                                id="btnAgregarDireccion"
                            >
                                Agregar dirección
                            </button>

                        </div>

                    <?php else: ?>

                        <div class="cuenta-direcciones-lista">

                            <?php foreach ($direcciones as $direccionCliente): ?>

                                <article
                                    class="cuenta-direccion-card"
                                    data-direccion-id="<?= (int) $direccionCliente["id_direccion"] ?>"
                                >

                                    <div class="cuenta-direccion-superior">

                                        <div>

                                            <span>
                                                DIRECCIÓN
                                            </span>

                                            <h3>
                                                <?= htmlspecialchars(
                                                    $direccionCliente["nombre"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </h3>

                                        </div>


                                        <?php if (
                                            (int) $direccionCliente["principal"] === 1
                                        ): ?>

                                            <span class="cuenta-direccion-principal">
                                                Principal
                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <div class="cuenta-direccion-datos">

                                        <p>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $direccionCliente["receptor"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </strong>
                                        </p>


                                        <p>
                                            <?= htmlspecialchars(
                                                $direccionCliente["direccion"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </p>


                                        <?php if (
                                            !empty($direccionCliente["barrio"])
                                        ): ?>

                                            <p>
                                                Barrio:
                                                <?= htmlspecialchars(
                                                    $direccionCliente["barrio"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </p>

                                        <?php endif; ?>


                                        <p>
                                            <?= htmlspecialchars(
                                                $direccionCliente["municipio"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>,
                                            <?= htmlspecialchars(
                                                $direccionCliente["departamento"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </p>


                                        <p>
                                            Tel.
                                            <?= htmlspecialchars(
                                                $direccionCliente["telefono"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </p>


                                        <?php if (
                                            !empty($direccionCliente["referencia"])
                                        ): ?>

                                            <p>
                                                Referencia:
                                                <?= htmlspecialchars(
                                                    $direccionCliente["referencia"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </p>

                                        <?php endif; ?>

                                    </div>


                                    <div class="cuenta-direccion-acciones">

                                        <button
                                            type="button"
                                            class="cuenta-boton-secundario"
                                            data-editar-direccion

                                            data-direccion-id="<?= (int) $direccionCliente["id_direccion"] ?>"

                                            data-nombre="<?= htmlspecialchars(
                                                $direccionCliente["nombre"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"

                                            data-receptor="<?= htmlspecialchars(
                                                $direccionCliente["receptor"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"

                                            data-telefono="<?= htmlspecialchars(
                                                $direccionCliente["telefono"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"

                                            data-direccion="<?= htmlspecialchars(
                                                $direccionCliente["direccion"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"

                                            data-barrio="<?= htmlspecialchars(
                                                $direccionCliente["barrio"] ?? "",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"

                                            data-municipio="<?= htmlspecialchars(
                                                $direccionCliente["municipio"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"

                                            data-departamento="<?= htmlspecialchars(
                                                $direccionCliente["departamento"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"

                                            data-referencia="<?= htmlspecialchars(
                                                $direccionCliente["referencia"] ?? "",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"

                                            data-principal="<?= (int) $direccionCliente["principal"] ?>"
                                        >
                                            Editar
                                        </button>

                                        <?php if (
                                            (int) $direccionCliente["principal"] !== 1
                                        ): ?>

                                            <button
                                                type="button"
                                                class="cuenta-boton-secundario"
                                                data-principal-direccion
                                                data-direccion-id="<?= (int) $direccionCliente["id_direccion"] ?>"
                                            >
                                                Hacer principal
                                            </button>

                                        <?php endif; ?>


                                        <button
                                            type="button"
                                            class="cuenta-direccion-eliminar"
                                            data-eliminar-direccion
                                            data-direccion-id="<?= (int) $direccionCliente["id_direccion"] ?>"
                                        >
                                            Eliminar
                                        </button>

                                    </div>

                                </article>

                            <?php endforeach; ?>


                            <div class="cuenta-vista-acciones">

                                <button
                                    type="button"
                                    class="cuenta-boton-primario"
                                    id="btnAgregarDireccion"
                                >
                                    Agregar dirección
                                </button>

                            </div>

                        </div>

                    <?php endif; ?>


                    <div class="cuenta-direcciones">

                        <div class="cuenta-seccion-encabezado">
                            <div>
                                <h2>Mis direcciones</h2>

                                <p>
                                    Administra las direcciones que utilizas
                                    para recibir tus pedidos.
                                </p>
                            </div>
                        </div>


                        <?php if ($direccionExito !== ""): ?>

                            <div class="cuenta-mensaje cuenta-mensaje-exito">
                                <?= htmlspecialchars(
                                    $direccionExito,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </div>

                        <?php endif; ?>


                        <?php if ($direccionError !== ""): ?>

                            <div class="cuenta-mensaje cuenta-mensaje-error">
                                <?= htmlspecialchars(
                                    $direccionError,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </div>

                        <?php endif; ?>


                        <?php if (!empty($direccionErrores)): ?>

                            <div class="cuenta-mensaje cuenta-mensaje-error">

                                <ul>

                                    <?php foreach ($direccionErrores as $error): ?>

                                        <li>
                                            <?= htmlspecialchars(
                                                $error,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </li>

                                    <?php endforeach; ?>

                                </ul>

                            </div>

                        <?php endif; ?>


                        <!-- ===================================
                            FORMULARIO NUEVA DIRECCIÓN
                        ==================================== -->

                        <div
                            class="cuenta-direccion-formulario"
                            <?= !empty($direccionErrores) ||
                                $editandoDireccion
                                ? ""
                                : "hidden" ?>
                        >

                            <div class="cuenta-direccion-formulario-titulo">

                                <h3 id="tituloFormularioDireccion">
                                    <?= $editandoDireccion
                                        ? "Editar dirección"
                                        : "Agregar nueva dirección" ?>
                                </h3>

                                <p>
                                    Completa la información de entrega.
                                </p>

                            </div>


                            <form
                                action="<?= $editandoDireccion
                                    ? "actualizar_direccion.php"
                                    : "guardar_direccion.php" ?>"
                                method="POST"
                                autocomplete="on"
                                id="formDireccion"
                            >

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= htmlspecialchars(
                                        $csrfDirecciones,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id_direccion"
                                    id="direccion_id"
                                    value="<?= $idDireccionEditar ?>"
                                >


                                <div class="cuenta-form-grid">


                                    <!-- NOMBRE DE LA DIRECCIÓN -->

                                    <div class="cuenta-form-grupo">

                                        <label for="direccion_nombre">
                                            Nombre de la dirección
                                        </label>

                                        <input
                                            type="text"
                                            id="direccion_nombre"
                                            name="nombre"
                                            maxlength="50"
                                            required
                                            placeholder="Ej: Casa, finca, trabajo"
                                            value="<?= htmlspecialchars(
                                                $dirNombre,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                    </div>


                                    <!-- RECEPTOR -->

                                    <div class="cuenta-form-grupo">

                                        <label for="direccion_receptor">
                                            Persona que recibe
                                        </label>

                                        <input
                                            type="text"
                                            id="direccion_receptor"
                                            name="receptor"
                                            maxlength="100"
                                            required
                                            autocomplete="name"
                                            placeholder="Nombre completo"
                                            value="<?= htmlspecialchars(
                                                $dirReceptor,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                    </div>


                                    <!-- TELÉFONO -->

                                    <div class="cuenta-form-grupo">

                                        <label for="direccion_telefono">
                                            Teléfono
                                        </label>

                                        <input
                                            type="tel"
                                            id="direccion_telefono"
                                            name="telefono"
                                            maxlength="20"
                                            required
                                            autocomplete="tel"
                                            placeholder="Ej: 3001234567"
                                            value="<?= htmlspecialchars(
                                                $dirTelefono,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                    </div>


                                    <!-- DIRECCIÓN -->

                                    <div class="cuenta-form-grupo">

                                        <label for="direccion_direccion">
                                            Dirección
                                        </label>

                                        <input
                                            type="text"
                                            id="direccion_direccion"
                                            name="direccion"
                                            maxlength="120"
                                            required
                                            autocomplete="street-address"
                                            placeholder="Ej: Calle 10 # 5-20"
                                            value="<?= htmlspecialchars(
                                                $dirDireccion,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                    </div>


                                    <!-- BARRIO -->

                                    <div class="cuenta-form-grupo">

                                        <label for="direccion_barrio">
                                            Barrio
                                            <span>(opcional)</span>
                                        </label>

                                        <input
                                            type="text"
                                            id="direccion_barrio"
                                            name="barrio"
                                            maxlength="60"
                                            placeholder="Ej: Centro"
                                            value="<?= htmlspecialchars(
                                                $dirBarrio,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                    </div>


                                    <!-- MUNICIPIO -->

                                    <div class="cuenta-form-grupo">

                                        <label for="direccion_municipio">
                                            Municipio
                                        </label>

                                        <input
                                            type="text"
                                            id="direccion_municipio"
                                            name="municipio"
                                            maxlength="50"
                                            required
                                            autocomplete="address-level2"
                                            placeholder="Ej: Purificación"
                                            value="<?= htmlspecialchars(
                                                $dirMunicipio,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                    </div>


                                    <!-- DEPARTAMENTO -->

                                    <div class="cuenta-form-grupo">

                                        <label for="direccion_departamento">
                                            Departamento
                                        </label>

                                        <input
                                            type="text"
                                            id="direccion_departamento"
                                            name="departamento"
                                            maxlength="50"
                                            required
                                            autocomplete="address-level1"
                                            placeholder="Ej: Tolima"
                                            value="<?= htmlspecialchars(
                                                $dirDepartamento,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                        >

                                    </div>


                                    <!-- REFERENCIA -->

                                    <div class="cuenta-form-grupo cuenta-form-grupo-completo">

                                        <label for="direccion_referencia">
                                            Referencia
                                            <span>(opcional)</span>
                                        </label>

                                        <textarea
                                            id="direccion_referencia"
                                            name="referencia"
                                            maxlength="150"
                                            rows="3"
                                            placeholder="Ej: Casa de dos pisos, portón verde..."
                                        ><?= htmlspecialchars(
                                            $dirReferencia,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?></textarea>

                                    </div>


                                </div>


                                <!-- PRINCIPAL -->

                                <label class="cuenta-direccion-principal-opcion">

                                    <input
                                        type="checkbox"
                                        name="principal"
                                        value="1"
                                        <?= $dirPrincipal
                                            ? "checked"
                                            : "" ?>
                                    >

                                    <span>
                                        Usar como mi dirección principal
                                    </span>

                                </label>


                                <!-- BOTÓN -->

                                <div class="cuenta-direccion-acciones">

                                    <button
                                        type="submit"
                                        class="btn-cuenta-principal"
                                        id="btnGuardarDireccion"
                                    >
                                        <?= $editandoDireccion
                                            ? "Guardar cambios"
                                            : "Guardar dirección" ?>
                                    </button>

                                    <button
                                        type="button"
                                        class="cuenta-boton-secundario"
                                        id="btnCancelarDireccion"
                                        hidden
                                    >
                                        Cancelar edición
                                    </button>

                                </div>

                            </form>

                        </div>

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

<?php
require_once __DIR__ . "/../includes/modal_producto.php";
?>

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