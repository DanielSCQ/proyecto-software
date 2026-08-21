<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");
    exit();

}


require_once("../../config/conexion.php");


/*
=================================
    VERIFICAR ID DE CATEGORÍA
=================================
*/

if (!isset($_GET["id"]) || !ctype_digit($_GET["id"])) {

    header("Location: categorias.php");
    exit();

}

$id_categoria = (int) $_GET["id"];


/*
=================================
    OBTENER CATEGORÍA ACTUAL
=================================
*/

$sql = "SELECT * FROM categorias WHERE id_categoria = ?";

$stmt = $conexion->prepare($sql);

$stmt->bind_param("i", $id_categoria);

$stmt->execute();

$resultado = $stmt->get_result();

$categoria = $resultado->fetch_assoc();

$stmt->close();


if (!$categoria) {

    header("Location: categorias.php");
    exit();

}


/*
=================================
    ACTUALIZAR CATEGORÍA
=================================
*/

$errores = [];

$nombre = $categoria["nombre"];
$descripcion = $categoria["descripcion"];
$estado = (string) $categoria["estado"];


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $estado = $_POST["estado"] ?? "";


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
        SI NO HAY ERRORES → ACTUALIZAR
    =================================
    */

    if (empty($errores)) {

        $sql = "UPDATE categorias
                SET nombre = ?,
                    descripcion = ?,
                    estado = ?
                WHERE id_categoria = ?";

        $stmt = $conexion->prepare($sql);


        if (!$stmt) {

            $errores[] = "No fue posible preparar la actualización.";

        } else {

            $estadoInt = (int) $estado;

            $stmt->bind_param(
                "ssii",
                $nombre,
                $descripcion,
                $estadoInt,
                $id_categoria
            );


            if ($stmt->execute()) {

                $stmt->close();

                header("Location: categorias.php");
                exit();

            } else {

                $errores[] = "No fue posible actualizar la categoría.";

            }

            $stmt->close();

        }

    }

}

?>


<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Categoría</title>

    <link rel="stylesheet" href="categorias.css">

</head>


<body>

<div class="contenedor-dashboard">


    <!-- MENÚ LATERAL -->

    <aside class="menu-lateral">

        <div class="logo-panel">

            <h1>AGRANDA</h1>

            <p>Panel administrativo</p>

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

    <!-- CONTENIDO -->

    <main class="contenido">

        <!-- ENCABEZADO -->

        <header class="encabezado">

            <div class="titulo-panel">

                <h2>¡Bienvenido,<?php echo htmlspecialchars($_SESSION["nombre"], ENT_QUOTES, "UTF-8"); ?>!</h2>

                <p>Panel de Administración AGRANDA</p>

            </div>

            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

                    <span id="hora"></span>

                </div>


                <div class="usuario">👤<?php echo htmlspecialchars($_SESSION["nombre"], ENT_QUOTES, "UTF-8"); ?></div>

                <a href="../cerrar_sesion.php" class="btn-salir">Cerrar sesión</a>

            </div>

        </header>

        <!-- FORMULARIO -->

        <div class="principal">

            <h2 class="titulo-formulario">✏️ Editar Categoría</h2>

            <!-- MENSAJES DE ERROR -->

            <?php if (!empty($errores)) { ?>

                <div class="mensaje-error">

                    <strong>🛑 No se puede actualizar la categoría.</strong>

                    <ul>

                        <?php foreach ($errores as $error) { ?>

                            <li>

                                <?php
                                echo htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </li>

                        <?php } ?>

                    </ul>

                </div>

            <?php } ?>


            <form method="POST">


                <!-- NOMBRE -->

                <label>
                    Nombre
                </label>


                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    required
                    value="<?php
                        echo htmlspecialchars(
                            $nombre,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>"
                    style="width:100%;padding:10px;"
                >


                <div
                    id="contador-nombre"
                    style="
                        text-align:right;
                        margin-top:5px;
                        color:#666;
                        font-size:13px;
                    "
                >
                    0 / 200 caracteres
                </div>


                <!-- DESCRIPCIÓN -->

                <label>
                    Descripción
                </label>


                <textarea
                    name="descripcion"
                    id="descripcion"
                    rows="5"
                    style="width:100%;padding:10px;"
                ><?php
                    echo htmlspecialchars(
                        $descripcion,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?></textarea>


                <div
                    id="contador-descripcion"
                    style="
                        text-align:right;
                        margin-top:5px;
                        color:#666;
                        font-size:13px;
                    "
                >
                    0 / 300 caracteres
                </div>

                <!-- ESTADO -->

                <label>Estado</label>


                <select name="estado">

                    <option
                        value="1"
                        <?php
                        if ($estado === "1") {
                            echo "selected";
                        }
                        ?>
                    >
                        Activa
                    </option>


                    <option
                        value="0"
                        <?php
                        if ($estado === "0") {
                            echo "selected";
                        }
                        ?>
                    >
                        Inactiva
                    </option>

                </select>

                <br><br>

                <!-- BOTONES -->

                <button class="btn-actualizar"
                    type="submit">💾 Actualizar Categoría</button>


                <a href="categorias.php"
                    class="btn-cancelar">Cancelar</a>

            </form>

        </div>

    </main>
</div>
<script src="../dashboard/dashboard.js"></script>

<script>

/*
=================================
    ELEMENTOS
=================================
*/

const nombre = document.getElementById("nombre");

const contadorNombre =
    document.getElementById("contador-nombre");

const descripcion =
    document.getElementById("descripcion");

const contadorDescripcion =
    document.getElementById("contador-descripcion");

const formulario =
    document.querySelector("form");


/*
=================================
    CONTADOR NOMBRE
=================================
*/

function actualizarContadorNombre() {

    const cantidad = nombre.value.length;

    contadorNombre.textContent =
        cantidad + " / 200 caracteres";


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
    CONTADOR DESCRIPCIÓN
=================================
*/

function actualizarContadorDescripcion() {

    const cantidad = descripcion.value.length;

    contadorDescripcion.textContent =
        cantidad + " / 300 caracteres";


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

nombre.addEventListener(
    "input",
    actualizarContadorNombre
);

descripcion.addEventListener(
    "input",
    actualizarContadorDescripcion
);


/*
=================================
    ACTUALIZAR AL CARGAR
=================================
*/

actualizarContadorNombre();

actualizarContadorDescripcion();


/*
=================================
    DETENER ENVÍO
=================================
*/

formulario.addEventListener(
    "submit",
    function(event) {

        const longitudNombre =
            nombre.value.length;

        const longitudDescripcion =
            descripcion.value.length;


        if (longitudNombre > 200) {

            event.preventDefault();

            alert(
                "🛑 El nombre de la categoría no puede superar los 200 caracteres."
            );

            nombre.focus();

            return;

        }


        if (longitudDescripcion > 300) {

            event.preventDefault();

            alert(
                "🛑 La descripción no puede superar los 300 caracteres."
            );

            descripcion.focus();

            return;

        }

    }
);

</script>
</body>
</html>