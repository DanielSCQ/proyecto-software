<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

?>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $nit = trim($_POST["nit"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $contacto_principal = trim($_POST["contacto_principal"] ?? "");
    $estado = $_POST["estado"] ?? "";

    $errores = [];

    if ($nombre === "") {

        $errores[] = "El nombre del proveedor es obligatorio.";

    } elseif (mb_strlen($nombre) > 150) {

        $errores[] = "El nombre del proveedor no puede superar los 150 caracteres.";

    }

    if ($nit === "") {

        $errores[] = "El NIT es obligatorio.";

    } elseif (mb_strlen($nit) > 20) {

    $errores[] = "El NIT no puede superar los 20 caracteres.";

    

    } elseif (!preg_match('/^[0-9\-]+$/', $nit)) {

        $errores[] = "El NIT solo puede contener números y guiones.";

    }

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

    if ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $errores[] = "El correo electrónico no es válido.";

    } elseif (mb_strlen($correo) > 100) {

        $errores[] = "El correo electrónico no puede superar los 100 caracteres.";

    }

    if (mb_strlen($direccion) > 250) {

        $errores[] = "La dirección no puede superar los 250 caracteres.";

    }

    if (mb_strlen($contacto_principal) > 100) {

        $errores[] = "El contacto principal no puede superar los 100 caracteres.";

    }

    if ($estado !== "0" && $estado !== "1") {

        $errores[] = "El estado seleccionado no es válido.";

    }

    if (empty($errores)) {

        $sql = "INSERT INTO proveedores
        (nombre, nit, telefono, correo, direccion, contacto_principal, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {

            $errores[] = "No fue posible preparar el registro del proveedor.";

        } else {

            $estadoInt = (int) $estado;

            $stmt->bind_param(
                "ssssssi",
                $nombre,
                $nit,
                $telefono,
                $correo,
                $direccion,
                $contacto_principal,
                $estadoInt
            );

           if ($stmt->execute()) {

                header("Location: proveedores.php");
                exit();

            } else {

                if ($stmt->errno == 1062) {

                    $errores[] = "El NIT ingresado ya está registrado. No se pueden registrar dos proveedores con el mismo NIT.";

                } else {

                    $errores[] = "No fue posible guardar el proveedor.";

                }

            }

            $stmt->close();

        }
    }

}

    $sql = "SELECT * FROM proveedores ORDER BY 
    id_proveedor DESC";

    $resultado =$conexion->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>gestor de proveedores</title>

    <link rel="stylesheet" href="proveedores.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- Menú lateral -->
    <aside class="menu-lateral">

        <div class="logo-panel">
            <h1>AGRANDA</h1>
            <p>proveedores</p>
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

        <!-- Área de trabajo -->
        <section class="resumen marcas-contenedor">

            <h2>🚚 Gestor de Proveedores</h2>

            <p>Desde aquí podrás administrar los proveedores de AGRANDA.</p>

            <?php if (!empty($errores)) { ?>

            <div class="mensaje-error">

                <strong>🛑 No se puede guardar el proveedor.</strong>

                <ul>

                    <?php foreach ($errores as $error) { ?>

                        <li>
                            <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
                        </li>

                    <?php } ?>

                </ul>

            </div>

        <?php } ?>

        <form method="POST" class="formulario-proveedores">

            <label>Nombre del proveedor</label>

                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    maxlength="150"
                    value="<?php echo htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    required>

                <div id="contador-nombre"
                style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 150 caracteres
                </div>

            <label>NIT</label>

                <input
                    type="text"
                    name="nit"
                    id="nit"
                    maxlength="20"
                    value="<?php echo htmlspecialchars($_POST['nit'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    required>

                <div id="contador-nit"
                style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 20 caracteres
                </div>

            <label>Teléfono</label>

                <input
                    type="text"
                    name="telefono"
                    id="telefono"
                    maxlength="25"
                    value="<?php echo htmlspecialchars($_POST['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                <div id="contador-telefono"
                style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 25 caracteres
                </div>

            <label>Correo electrónico</label>

                <input
                    type="email"
                    name="correo"
                    id="correo"
                    maxlength="100"
                    value="<?php echo htmlspecialchars($_POST['correo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <label>Dirección</label>

                <textarea
                    name="direccion"
                    id="direccion"
                    rows="3"
                    maxlength="250"><?php echo htmlspecialchars($_POST['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

                <div id="contador-direccion"
                style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 250 caracteres
                </div>

            <label>Contacto principal</label>

                <input
                    type="text"
                    name="contacto_principal"
                    id="contacto_principal"
                    maxlength="100"
                    value="<?php echo htmlspecialchars($_POST['contacto_principal'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                <div id="contador-contacto"
                style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 100 caracteres
                </div>    

            <label>Estado</label>

            <select name="estado">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>

        <br><br>

            <button type="submit" class="btn-nuevo">
                💾 Guardar Proveedor
            </button>

        </form>
            
            <hr><br>

            <table class="tabla-proveedores">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>NIT</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Dirección</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while($proveedor = $resultado->fetch_assoc()){ ?>

                    <tr>

                        <td><?php echo htmlspecialchars($proveedor["id_proveedor"], ENT_QUOTES, "UTF-8"); ?></td>

                        <td><?php echo htmlspecialchars($proveedor["nombre"], ENT_QUOTES, "UTF-8"); ?></td>

                        <td><?php echo htmlspecialchars($proveedor["nit"], ENT_QUOTES, "UTF-8"); ?></td>

                        <td><?php echo htmlspecialchars($proveedor["telefono"], ENT_QUOTES, "UTF-8"); ?></td>

                        <td><?php echo htmlspecialchars($proveedor["correo"], ENT_QUOTES, "UTF-8"); ?></td>

                        <td><?php echo htmlspecialchars($proveedor["direccion"], ENT_QUOTES, "UTF-8"); ?></td>

                        <td><?php echo htmlspecialchars($proveedor["contacto_principal"], ENT_QUOTES, "UTF-8"); ?></td>

                        <td>

                            <?php if($proveedor["estado"]){ ?>

                                <span class="estado-activo">
                                    Activo
                                </span>

                            <?php } else { ?>

                                <span class="estado-inactivo">
                                    Inactivo
                                </span>

                            <?php } ?>

                        </td>

                        <td class="acciones">

                            <a href="editar_proveedor.php?id=<?php echo $proveedor["id_proveedor"]; ?>" class="btn-editar">
                                ✏️
                            </a>

                            <a href="eliminar_proveedor.php?id=<?php echo $proveedor["id_proveedor"]; ?>"
                            class="btn-eliminar"
                            onclick="return confirm('¿Deseas eliminar este proveedor?');">
                                🗑️
                            </a>

                        </td>

                    </tr>

                    <?php } ?>
                </tbody>

            </table>    

        </section>

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

    const campo = document.getElementById(item.campo);
    const contador = document.getElementById(item.contador);

    function actualizarContador() {

        const cantidad = campo.value.length;

        contador.textContent =
            cantidad + " / " + item.maximo + " caracteres";

        if (cantidad >= item.maximo) {

            contador.style.color = "#c62828";
            contador.style.fontWeight = "bold";

        } else {

            contador.style.color = "#666";
            contador.style.fontWeight = "normal";

        }

    }

    campo.addEventListener("input", actualizarContador);

    actualizarContador();

});

</script>

</body>
</html>