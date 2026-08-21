<?php

session_start();
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $estado = $_POST["estado"] ?? "";

    $errores = [];

    /*
    =================================
        VALIDAR NOMBRE
    =================================
    */

    if ($nombre === "") {

        $errores[] = "El nombre de la categoría es obligatorio.";

    } elseif (mb_strlen($nombre) > 200) {

        $errores[] = "El nombre de la categoría no puede superar los 200 caracteres.";

    }


    /*
    =================================
        VALIDAR DESCRIPCIÓN
    =================================
    */

    if (mb_strlen($descripcion) > 300) {

        $errores[] = "La descripción no puede superar los 300 caracteres.";

    }


    /*
    =================================
        VALIDAR ESTADO
    =================================
    */

    if ($estado !== "0" && $estado !== "1") {

        $errores[] = "El estado seleccionado no es válido.";

    }


    /*
    =================================
        VALIDAR IMAGEN
    =================================
    */

    $archivoImagen = null;

    if (!isset($_FILES["imagen"]) || $_FILES["imagen"]["error"] === UPLOAD_ERR_NO_FILE) {

        $errores[] = "Debes seleccionar una imagen.";

    } elseif ($_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {

        $errores[] = "Ocurrió un error al subir la imagen.";

    } else {

        $archivoImagen = $_FILES["imagen"];

        // Máximo 2 MB
        $tamanoMaximo = 2 * 1024 * 1024;

        if ($archivoImagen["size"] > $tamanoMaximo) {

            $errores[] = "La imagen no puede superar los 2 MB.";

        }


        // Comprobar el tipo real del archivo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $tipoMime = finfo_file($finfo, $archivoImagen["tmp_name"]);
        finfo_close($finfo);

        $tiposPermitidos = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!in_array($tipoMime, $tiposPermitidos, true)) {

            $errores[] = "La imagen debe ser JPG, JPEG, PNG o WEBP.";

        }

    }


    /*
    =================================
        SI NO HAY ERRORES → GUARDAR
    =================================
    */

    if (empty($errores)) {

        $carpeta = "../../uploads/categorias/";

        // Crear carpeta si no existe
        if (!is_dir($carpeta)) {

            mkdir($carpeta, 0755, true);

        }

        /*
        Generamos un nombre seguro para la imagen.
        No utilizamos directamente el nombre enviado
        por el usuario.
        */

        $extension = match ($tipoMime) {
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        };

        $nombreImagen = bin2hex(random_bytes(16)) . "." . $extension;

        $rutaDestino = $carpeta . $nombreImagen;

        if (!move_uploaded_file($archivoImagen["tmp_name"], $rutaDestino)) {

            $errores[] = "No fue posible guardar la imagen.";

        } else {

            $imagen = "uploads/categorias/" . $nombreImagen;

            $sql = "INSERT INTO categorias(nombre, descripcion, imagen, estado)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {

                // Si falla la consulta, eliminamos la imagen que acabamos de subir
                if (file_exists($rutaDestino)) {
                    unlink($rutaDestino);
                }

                $errores[] = "No fue posible preparar el registro.";

            } else {

                $stmt->bind_param(
                    "sssi",
                    $nombre,
                    $descripcion,
                    $imagen,
                    $estado
                );

                if ($stmt->execute()) {

                    header("Location: categorias.php");
                    exit();

                } else {

                    // Si falla la BD, tampoco dejamos la imagen abandonada
                    if (file_exists($rutaDestino)) {
                        unlink($rutaDestino);
                    }

                    $errores[] = "No fue posible guardar la categoría.";

                }

                $stmt->close();

            }

        }

    }

}
?>

<!DOCTYPE html>
<html lang="es">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nueva Categoría</title>

    <link rel="stylesheet" href="categorias.css">

</head>

<body>

    <div class="contenedor-dashboard">

    <!-- Menú lateral -->
        <aside class="menu-lateral">

            <div class="logo-panel">

                <h1>AGRANDA</h1>

                <p>productos</p>

            </div>

            <nav>

                <ul>

                    <li><a href="../dashboard/dashboard.php">📊 Dashboard</a></li>

                    <li><a href="../productos/productos.php">📦 Productos</a></li>

                    <li><a href="categorias.php" class="activo">🗂️ Categorías</a></li>

                    <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                    <li><a href="../proveedores/proveedores.php">🚚 Proveedores</a></li>

                    <li><a href="../inventario/inventario.php">📁 Inventario</a></li>

                    <li><a href="../pedidos/pedidos.php">🛒 Pedidos</a></li>

                    <li><a href="../clientes/clientes.php">👥 Clientes</a></li>

                    <li><a href="../contactos/contactos.php">✉️ Contactos</a></li>

                    <li><a href="../promociones/promociones.php">🎁 Promociones</a></li>

                    <li><a href="../configuracion/configuracion.php">⚙️ Configuración</a></li>

                </ul>

            </nav>

        </aside>

        <!-- Contenido principal -->
        <main class="contenido">

            <!-- Encabezado -->
            <header class="encabezado">

                <div class="titulo-panel">
                    <h2>¡Bienvenido, <?php echo $_SESSION["nombre"]; ?>!</h2>
                        <p>Panel de Administración AGRANDA</p>
                </div>

                <div class="panel-usuario">

                    <div class="fecha-hora">
                        <span id="fecha"></span><br>
                        <span id="hora"></span>
                    </div>

                <div class="usuario">
                    👤 <?php echo $_SESSION["nombre"]; ?>
                </div>

                    <a href="../cerrar_sesion.php" class="btn-salir">
                        Cerrar sesión
                    </a>

                </div>

            </header>

    <div class="principal">

    <h2>➕ Nueva Categoría</h2>

    <form method="POST" enctype="multipart/form-data">

        <p>
            <label>Nombre</label><br>

            <input
                type="text"
                name="nombre"
                id="nombre"
                required
                value="<?php echo htmlspecialchars($nombre ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                style="width:100%;padding:10px;">

            <div id="contador-nombre" style="text-align:right; margin-top:5px; color:#666; font-size:13px;">
                0 / 200 caracteres
            </div>

        </p>

        <p>

            <label>Descripción</label><br>

            <textarea
                name="descripcion"
                id="descripcion"
                rows="5"
                style="width:100%;padding:10px;"><?php echo htmlspecialchars($descripcion ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

            <div id="contador-descripcion" style="text-align:right; margin-top:5px; color:#666; font-size:13px;">
                0 / 300 caracteres
            </div>

        </p>

        <p>

            <label>imagen</label><br>

                <input
                    type="file"
                    name="imagen"
                    accept=".jpg,.jpeg,.png,.webp"
                    required>
        </p>

        <p>
            <label>Estado</label><br>

            <select name="estado">

            <option value="1">Activa</option>

            <option value="0">Inactiva</option>

            </select>

        </p>

        <p>

        <br></br>
            <button type="submit" class="btn-nuevo">
                💾 Guardar Categoría
            </button>

            <a href="categorias.php" class="btn-cancelar">
                Cancelar
            </a>

        </p>

    </form>
    </div>
    </div>

<script>

const nombre = document.getElementById("nombre");
const contadorNombre = document.getElementById("contador-nombre");

const descripcion = document.getElementById("descripcion");
const contadorDescripcion = document.getElementById("contador-descripcion");


/*
=================================
    CONTADOR DEL NOMBRE
=================================
*/

function actualizarContadorNombre() {

    const cantidad = nombre.value.length;

    contadorNombre.textContent = cantidad + " / 200 caracteres";

    if (cantidad > 200) {

        contadorNombre.style.color = "#c62828";
        contadorNombre.style.fontWeight = "bold";

    } else {

        contadorNombre.style.color = "#666";
        contadorNombre.style.fontWeight = "normal";

    }

}


/*
=================================
    CONTADOR DE DESCRIPCIÓN
=================================
*/

function actualizarContadorDescripcion() {

    const cantidad = descripcion.value.length;

    contadorDescripcion.textContent = cantidad + " / 300 caracteres";

    if (cantidad > 300) {

        contadorDescripcion.style.color = "#c62828";
        contadorDescripcion.style.fontWeight = "bold";

    } else {

        contadorDescripcion.style.color = "#666";
        contadorDescripcion.style.fontWeight = "normal";

    }

}


/*
=================================
    ACTUALIZAR AL ESCRIBIR
=================================
*/

nombre.addEventListener("input", actualizarContadorNombre);

descripcion.addEventListener("input", actualizarContadorDescripcion);


/*
=================================
    ACTUALIZAR AL CARGAR
=================================
*/

actualizarContadorNombre();
actualizarContadorDescripcion();


/*
=================================
    DETENER ENVÍO SI SUPERA LÍMITES
=================================
*/

const formulario = document.querySelector("form");

formulario.addEventListener("submit", function(event) {

    const longitudNombre = nombre.value.length;
    const longitudDescripcion = descripcion.value.length;

    if (longitudNombre > 200) {

        event.preventDefault();

        alert("🛑 El nombre de la categoría no puede superar los 200 caracteres.");

        nombre.focus();

        return;

    }

    if (longitudDescripcion > 300) {

        event.preventDefault();

        alert("🛑 La descripción no puede superar los 300 caracteres.");

        descripcion.focus();

        return;

    }

});

</script>    

</body>
</html>