<?php

session_start();

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION["id_usuario"])) {

    header("Location: ../login.php");
    exit();

}

require_once("../../config/conexion.php");


// Verificar ID del proveedor
$id_proveedor = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id_proveedor === false || $id_proveedor === null || $id_proveedor <= 0) {

    header("Location: proveedores.php");
    exit();

}


$errores = [];


// Actualizar proveedor
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $nit = trim($_POST["nit"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $contacto_principal = trim($_POST["contacto_principal"] ?? "");
    $estado = $_POST["estado"] ?? "";


    // =========================
    // VALIDAR NOMBRE
    // =========================

    if ($nombre === "") {

        $errores[] = "El nombre del proveedor es obligatorio.";

    } elseif (mb_strlen($nombre) > 150) {

        $errores[] = "El nombre del proveedor no puede superar los 150 caracteres.";

    }


    // =========================
    // VALIDAR NIT
    // =========================

    if ($nit === "") {

        $errores[] = "El NIT es obligatorio.";

    } elseif (mb_strlen($nit) > 20) {

        $errores[] = "El NIT no puede superar los 20 caracteres.";

    } elseif (!preg_match('/^[0-9\-]+$/', $nit)) {

        $errores[] = "El NIT solo puede contener números y guiones.";

    }


    // =========================
    // VALIDAR TELÉFONO
    // =========================

    if ($telefono !== "") {

        if (!preg_match('/^[0-9+\-\s()]+$/', $telefono)) {

            $errores[] = "El teléfono solo puede contener números, espacios, guiones, paréntesis y el signo +.";

        } else {

            $digitosTelefono = preg_replace('/\D/', '', $telefono);

            if (strlen($digitosTelefono) < 7) {

                $errores[] = "El teléfono debe contener al menos 7 dígitos.";

            } elseif (strlen($digitosTelefono) > 15) {

                $errores[] = "El teléfono no puede contener más de 15 dígitos.";

            }

        }

    }


    // =========================
    // VALIDAR CORREO
    // =========================

    if ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $errores[] = "El correo electrónico no es válido.";

    } elseif (mb_strlen($correo) > 100) {

        $errores[] = "El correo electrónico no puede superar los 100 caracteres.";

    }


    // =========================
    // VALIDAR DIRECCIÓN
    // =========================

    if (mb_strlen($direccion) > 250) {

        $errores[] = "La dirección no puede superar los 250 caracteres.";

    }


    // =========================
    // VALIDAR CONTACTO
    // =========================

    if (mb_strlen($contacto_principal) > 100) {

        $errores[] = "El contacto principal no puede superar los 100 caracteres.";

    }


    // =========================
    // VALIDAR ESTADO
    // =========================

    if ($estado !== "0" && $estado !== "1") {

        $errores[] = "El estado seleccionado no es válido.";

    }


    // =========================
    // VERIFICAR NIT DUPLICADO
    // =========================

    if (empty($errores)) {

        $sqlVerificar = "SELECT id_proveedor
                         FROM proveedores
                         WHERE nit = ?
                         AND id_proveedor != ?
                         LIMIT 1";

        $stmtVerificar = $conexion->prepare($sqlVerificar);

        if (!$stmtVerificar) {

            $errores[] = "No fue posible verificar el NIT del proveedor.";

        } else {

            $stmtVerificar->bind_param(
                "si",
                $nit,
                $id_proveedor
            );

            $stmtVerificar->execute();

            $resultadoVerificacion = $stmtVerificar->get_result();

            if ($resultadoVerificacion->num_rows > 0) {

                $errores[] = "El NIT ingresado ya está registrado en otro proveedor.";

            }

            $stmtVerificar->close();

        }

    }


    // =========================
    // ACTUALIZAR PROVEEDOR
    // =========================

    if (empty($errores)) {

        $sql = "UPDATE proveedores
                SET nombre = ?,
                    nit = ?,
                    telefono = ?,
                    correo = ?,
                    direccion = ?,
                    contacto_principal = ?,
                    estado = ?
                WHERE id_proveedor = ?";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {

            $errores[] = "No fue posible preparar la actualización del proveedor.";

        } else {

            $estadoInt = (int) $estado;

            $stmt->bind_param(
                "ssssssii",
                $nombre,
                $nit,
                $telefono,
                $correo,
                $direccion,
                $contacto_principal,
                $estadoInt,
                $id_proveedor
            );


            if ($stmt->execute()) {

                $stmt->close();

                header("Location: proveedores.php");
                exit();

            } else {

                if ($stmt->errno == 1062) {

                    $errores[] = "El NIT ingresado ya está registrado. No se pueden registrar dos proveedores con el mismo NIT.";

                } else {

                    $errores[] = "No fue posible actualizar el proveedor.";

                }

                $stmt->close();

            }

        }

    }

}


// =========================
// OBTENER DATOS ACTUALES
// =========================

$sql = "SELECT * FROM proveedores WHERE id_proveedor = ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {

    header("Location: proveedores.php");
    exit();

}

$stmt->bind_param("i", $id_proveedor);

$stmt->execute();

$resultado = $stmt->get_result();

$proveedor = $resultado->fetch_assoc();

$stmt->close();


if (!$proveedor) {

    header("Location: proveedores.php");
    exit();

}


// Si NO hubo POST, mostrar los datos actuales.
// Si hubo POST con errores, conservar lo que escribió el usuario.
if ($_SERVER["REQUEST_METHOD"] != "POST") {

    $nombre = $proveedor["nombre"];
    $nit = $proveedor["nit"];
    $telefono = $proveedor["telefono"];
    $correo = $proveedor["correo"];
    $direccion = $proveedor["direccion"];
    $contacto_principal = $proveedor["contacto_principal"];
    $estado = (string) $proveedor["estado"];

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Proveedores</title>

    <link rel="stylesheet" href="proveedores.css">

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

                <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

                <li><a href="../marcas/marcas.php">🏷️ Marcas</a></li>

                <li><a href="proveedores.php" class="activo">🚚 Proveedores</a></li>

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

                <p>Panel de Administración AGRANDA</p>

            </div>

            <div class="panel-usuario">

                <div class="fecha-hora">

                    <span id="fecha"></span><br>

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
                    class="btn-salir">Cerrar sesión</a>

            </div>

        </header>

        <div class="principal">

            <h2 class="titulo-formulario">✏️ Editar Proveedores</h2>

            <?php if (!empty($errores)) { ?>

                <div class="mensaje-error">

                    <strong>🛑 No se puede actualizar el proveedor.</strong>

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

                <label>Nombre del proveedor</label>

                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    maxlength="150"
                    value="<?php
                    echo htmlspecialchars(
                        $nombre,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>"
                    required>


                <div
                    id="contador-nombre"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 150 caracteres
                </div>


                <label>NIT</label>

                <input
                    type="text"
                    name="nit"
                    id="nit"
                    maxlength="20"
                    value="<?php
                    echo htmlspecialchars(
                        $nit,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>"
                    required>

                <div
                    id="contador-nit"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 20 caracteres
                </div>

                <label>Teléfono</label>

                <input
                    type="text"
                    name="telefono"
                    id="telefono"
                    maxlength="25"
                    value="<?php
                    echo htmlspecialchars(
                        $telefono,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>">


                <div
                    id="contador-telefono"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 25 caracteres
                </div>

                <label>Correo electrónico</label>

                <input
                    type="email"
                    name="correo"
                    id="correo"
                    maxlength="100"
                    value="<?php
                    echo htmlspecialchars(
                        $correo,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>">


                <label>Dirección</label>

                <textarea
                    name="direccion"
                    id="direccion"
                    rows="3"
                    maxlength="250"><?php
                    echo htmlspecialchars(
                        $direccion,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?></textarea>


                <div
                    id="contador-direccion"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 250 caracteres
                </div>


                <label>Contacto principal</label>

                <input
                    type="text"
                    name="contacto_principal"
                    id="contacto_principal"
                    maxlength="100"
                    value="<?php
                    echo htmlspecialchars(
                        $contacto_principal,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>">

                <div
                    id="contador-contacto"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 100 caracteres
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
                        ?>>

                        Activo

                    </option>

                    <option
                        value="0"
                        <?php
                        if ($estado === "0") {
                            echo "selected";
                        }
                        ?>>

                        Inactivo

                    </option>

                </select>

                <br><br>

                <button class="btn-actualizar"type="submit">💾 Actualizar Proveedor</button>

                <a href="proveedores.php"class="btn-cancelar">Cancelar</a>

            </form>

        </div>
    </main>
</div>

<script src="../dashboard/dashboard.js"></script>

<script>

const campos = [

    {
        campo: "nombre",
        contador: "contador-nombre",
        maximo: 150
    },

    {
        campo: "nit",
        contador: "contador-nit",
        maximo: 20
    },

    {
        campo: "telefono",
        contador: "contador-telefono",
        maximo: 25
    },

    {
        campo: "direccion",
        contador: "contador-direccion",
        maximo: 250
    },

    {
        campo: "contacto_principal",
        contador: "contador-contacto",
        maximo: 100
    }

];


campos.forEach(function(item) {

    const campo =
        document.getElementById(item.campo);

    const contador =
        document.getElementById(item.contador);


    function actualizarContador() {

        const cantidad =
            campo.value.length;


        contador.textContent =
            cantidad +
            " / " +
            item.maximo +
            " caracteres";


        if (cantidad >= item.maximo) {

            contador.style.color = "#c62828";
            contador.style.fontWeight = "bold";

        } else {

            contador.style.color = "#666";
            contador.style.fontWeight = "normal";

        }

    }

    campo.addEventListener(
        "input",
        actualizarContador
    );

    actualizarContador();

});

</script>
</body>
</html>