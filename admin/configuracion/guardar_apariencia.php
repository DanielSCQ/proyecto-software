<?php

session_start();


// =================================
// PROTEGER ACCESO
// =================================

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}


require_once("../../config/conexion.php");


// =================================
// SOLO PERMITIR POST
// =================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: apariencia.php");
    exit();
}


// =================================
// VALIDAR CSRF
// =================================

$csrf = $_POST["csrf"] ?? "";

if (
    empty($_SESSION["csrf_apariencia"]) ||
    !hash_equals($_SESSION["csrf_apariencia"], $csrf)
) {
    header("Location: apariencia.php?error=csrf");
    exit();
}


// =================================
// CONFIGURACIÓN GENERAL
// =================================

$idConfiguracion = 1;

$idUsuario = (int) $_SESSION["id_usuario"];

$tamanoMaximo = 2 * 1024 * 1024; // 2 MB


$tiposPermitidos = [

    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"

];


// =================================
// CARPETA DE DESTINO
// =================================

$carpetaFisica =
    __DIR__ .
    "/../../uploads/tienda/";


$rutaBaseBD =
    "uploads/tienda/";


// =================================
// CREAR CARPETA SI NO EXISTE
// =================================

if (!is_dir($carpetaFisica)) {

    if (!mkdir($carpetaFisica, 0755, true)) {

        header(
            "Location: apariencia.php?error=carpeta"
        );

        exit();
    }
}


// =================================
// OBTENER CONFIGURACIÓN ACTUAL
// =================================

$stmt = $conexion->prepare(
    "SELECT
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

    header(
        "Location: apariencia.php?error=consulta"
    );

    exit();
}


$stmt->bind_param(
    "i",
    $idConfiguracion
);


$stmt->execute();


$resultado =
    $stmt->get_result();


$configuracionActual =
    $resultado->fetch_assoc();


$stmt->close();


// =================================
// VALIDAR CONFIGURACIÓN
// =================================

if (!$configuracionActual) {

    header(
        "Location: apariencia.php?error=configuracion"
    );

    exit();
}


// =================================
// VALORES INICIALES
// =================================

$nuevosValores = [

    "logo" =>
        $configuracionActual["logo"],

    "imagen_hero" =>
        $configuracionActual["imagen_hero"],

    "imagen_login_admin" =>
        $configuracionActual["imagen_login_admin"],

    "imagen_fondo_login_admin" =>
        $configuracionActual["imagen_fondo_login_admin"],

    "imagen_dashboard_admin" =>
        $configuracionActual["imagen_dashboard_admin"]

];


// =================================
// ARCHIVOS NUEVOS
// =================================

// Se guardan aquí para poder eliminarlos
// si falla la actualización de la BD.

$archivosNuevos = [];


// =================================
// ARCHIVOS ANTIGUOS
// =================================

// Solo se eliminan después de que la BD
// se haya actualizado correctamente.

$archivosAntiguos = [];


// =================================
// CAMPOS DE IMAGEN
// =================================

$camposImagen = [

    "logo",

    "imagen_hero",

    "imagen_login_admin",

    "imagen_fondo_login_admin",

    "imagen_dashboard_admin"

];


// =================================
// PROCESAR IMÁGENES
// =================================

foreach ($camposImagen as $campo) {


    // =================================
    // NO SE SELECCIONÓ ARCHIVO NUEVO
    // =================================

    if (
        !isset($_FILES[$campo]) ||
        $_FILES[$campo]["error"]
            === UPLOAD_ERR_NO_FILE
    ) {

        continue;
    }


    $archivo =
        $_FILES[$campo];


    // =================================
    // ERROR DE SUBIDA
    // =================================

    if ($archivo["error"] !== UPLOAD_ERR_OK) {


        foreach (
            $archivosNuevos
            as $archivoNuevo
        ) {

            if (is_file($archivoNuevo)) {

                unlink($archivoNuevo);
            }
        }


        header(
            "Location: apariencia.php?error=subida"
        );

        exit();
    }


    // =================================
    // VALIDAR TAMAÑO
    // =================================

    if (
        $archivo["size"]
        > $tamanoMaximo
    ) {


        foreach (
            $archivosNuevos
            as $archivoNuevo
        ) {

            if (is_file($archivoNuevo)) {

                unlink($archivoNuevo);
            }
        }


        header(
            "Location: apariencia.php?error=tamano"
        );

        exit();
    }


    // =================================
    // VALIDAR SUBIDA REAL
    // =================================

    if (
        !is_uploaded_file(
            $archivo["tmp_name"]
        )
    ) {


        foreach (
            $archivosNuevos
            as $archivoNuevo
        ) {

            if (is_file($archivoNuevo)) {

                unlink($archivoNuevo);
            }
        }


        header(
            "Location: apariencia.php?error=archivo"
        );

        exit();
    }


    // =================================
    // VALIDAR MIME REAL
    // =================================

    $finfo =
        new finfo(
            FILEINFO_MIME_TYPE
        );


    $mime =
        $finfo->file(
            $archivo["tmp_name"]
        );


    if (
        !isset(
            $tiposPermitidos[$mime]
        )
    ) {


        foreach (
            $archivosNuevos
            as $archivoNuevo
        ) {

            if (is_file($archivoNuevo)) {

                unlink($archivoNuevo);
            }
        }


        header(
            "Location: apariencia.php?error=formato"
        );

        exit();
    }


    // =================================
    // GENERAR NOMBRE SEGURO
    // =================================

    $extension =
        $tiposPermitidos[$mime];


    $nombreAleatorio =
        bin2hex(
            random_bytes(16)
        );


    $nombreArchivo =
        $campo .
        "_" .
        $nombreAleatorio .
        "." .
        $extension;


    // =================================
    // RUTAS
    // =================================

    $rutaFisicaNueva =
        $carpetaFisica .
        $nombreArchivo;


    $rutaBDNueva =
        $rutaBaseBD .
        $nombreArchivo;


    // =================================
    // MOVER ARCHIVO
    // =================================

    if (
        !move_uploaded_file(
            $archivo["tmp_name"],
            $rutaFisicaNueva
        )
    ) {


        foreach (
            $archivosNuevos
            as $archivoNuevo
        ) {

            if (is_file($archivoNuevo)) {

                unlink($archivoNuevo);
            }
        }


        header(
            "Location: apariencia.php?error=guardar_archivo"
        );

        exit();
    }


    // Guardamos la ruta del archivo nuevo
    // por si luego hay que eliminarlo.

    $archivosNuevos[] =
        $rutaFisicaNueva;


    // =================================
    // PREPARAR ARCHIVO ANTIGUO
    // =================================

    if (
        !empty(
            $configuracionActual[$campo]
        )
    ) {


        $rutaAntigua =
            $configuracionActual[$campo];


        // Solo se permite borrar imágenes
        // ubicadas dentro de uploads/tienda/

        if (
            str_starts_with(
                $rutaAntigua,
                "uploads/tienda/"
            )
        ) {


            $archivosAntiguos[] =
                __DIR__ .
                "/../../" .
                $rutaAntigua;

        }
    }


    // =================================
    // ACTUALIZAR VALOR TEMPORAL
    // =================================

    $nuevosValores[$campo] =
        $rutaBDNueva;

}


// =================================
// ACTUALIZAR BASE DE DATOS
// =================================

try {


    $conexion->begin_transaction();


    $stmt = $conexion->prepare(
        "UPDATE configuracion_tienda

         SET

            logo = ?,

            imagen_hero = ?,

            imagen_login_admin = ?,

            imagen_fondo_login_admin = ?,

            imagen_dashboard_admin = ?,

            id_usuario = ?

         WHERE id_configuracion = ?"
    );


    if (!$stmt) {

        throw new Exception(
            "No se pudo preparar la actualización."
        );
    }


    // =================================
    // 5 STRINGS + 2 ENTEROS
    // =================================

    $stmt->bind_param(

        "sssssii",

        $nuevosValores["logo"],

        $nuevosValores["imagen_hero"],

        $nuevosValores["imagen_login_admin"],

        $nuevosValores["imagen_fondo_login_admin"],

        $nuevosValores["imagen_dashboard_admin"],

        $idUsuario,

        $idConfiguracion

    );


    if (!$stmt->execute()) {

        throw new Exception(
            "No se pudo actualizar la configuración."
        );
    }


    $stmt->close();


    $conexion->commit();


} catch (Throwable $e) {


    $conexion->rollback();


    // =================================
    // BORRAR NUEVAS SI FALLÓ LA BD
    // =================================

    foreach (
        $archivosNuevos
        as $archivoNuevo
    ) {

        if (is_file($archivoNuevo)) {

            unlink($archivoNuevo);
        }
    }


    header(
        "Location: apariencia.php?error=base_datos"
    );

    exit();

}


// =================================
// ELIMINAR IMÁGENES ANTIGUAS
// =================================

foreach (
    $archivosAntiguos
    as $archivoAntiguo
) {


    if (is_file($archivoAntiguo)) {

        unlink($archivoAntiguo);
    }

}


// =================================
// GENERAR NUEVO TOKEN CSRF
// =================================

$_SESSION["csrf_apariencia"] =
    bin2hex(
        random_bytes(32)
    );


// =================================
// REDIRECCIÓN FINAL
// =================================

header(
    "Location: apariencia.php?actualizado=1"
);

exit();