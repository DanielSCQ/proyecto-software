<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

$sql = "SELECT * FROM categorias ORDER BY id_categoria DESC";

$resultado = $conexion->query($sql);

// Obtener todas las categorías disponibles para reasignar productos
$sqlCategoriasDisponibles = "
    SELECT id_categoria, nombre
    FROM categorias
    ORDER BY nombre ASC
";

$resultadoCategoriasDisponibles = $conexion->query($sqlCategoriasDisponibles);

$categoriasDisponibles = [];

while ($cat = $resultadoCategoriasDisponibles->fetch_assoc()) {
    $categoriasDisponibles[] = $cat;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Categorías | AGRANDA</title>

    <link rel="stylesheet" href="categorias.css">

</head>

<body>

<div class="contenedor-dashboard">

    <!-- Menú lateral -->
    <aside class="menu-lateral">

        <div class="logo-panel">
            <h1>AGRANDA</h1>
            <p>Categorías</p>
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

    <!-- Contenido -->
    <main class="contenido">

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

        <section class="resumen">

            <h2>🗂️ Gestor de Categorías</h2>

            <p>
                Desde aquí podrás agregar, editar y eliminar las categorías de AGRANDA.
            </p>

            <div class="barra-productos">

                <a href="agregar_categoria.php" class="btn-nuevo">
                    ➕ Nueva Categoría
                </a>

                <input
                    type="search"
                    class="buscar-producto"
                    placeholder="Buscar categoría..."
                >

            </div>

            <table class="tabla-productos">

                <thead>

                    <tr>

                        <th>Imagen</th>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                <?php while($categoria = $resultado->fetch_assoc()){ ?>

                    <tr>

                        <td>

                            <?php if(!empty($categoria["imagen"])){ ?>

                                <img
                                    src="../../<?php echo $categoria["imagen"]; ?>"
                                    alt="Categoría"
                                    width="100">

                            <?php }else{ ?>

                                Sin imagen

                            <?php } ?>

                        </td>

                        <td>
                            <?php echo $categoria["id_categoria"]; ?>
                        </td>

                        <td>
                            <?php echo $categoria["nombre"]; ?>
                        </td>

                        <td>
                            <?php echo $categoria["descripcion"]; ?>
                        </td>

                        <td>

                            <?php if($categoria["estado"]){ ?>

                                <span class="estado-activo">
                                    Activa
                                </span>

                            <?php } else { ?>

                                <span class="estado-inactivo">
                                    Inactiva
                                </span>

                            <?php } ?>

                        </td>

                        <td class="acciones">

                            <a href="editar_categoria.php?id=<?php echo $categoria["id_categoria"]; ?>"
                                class="btn-editar">✏️</a>
                            
                            <button
                                type="button"
                                class="btn-eliminar"
                                onclick="abrirVentanaEliminar(
                                    <?php echo (int) $categoria["id_categoria"]; ?>,
                                    '<?php echo htmlspecialchars($categoria["nombre"], ENT_QUOTES, "UTF-8"); ?>'
                                )">
                                🗑️
                            </button>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </section>

    </main>

</div>

<!-- VENTANA FLOTANTE PARA ELIMINAR CATEGORÍA -->

<div id="ventana-eliminar-categoria" class="ventana-eliminar">

    <div class="ventana-eliminar-contenido">

        <div
            id="barra-ventana-eliminar"
            class="ventana-eliminar-header">

            <span>
                ⚠️ Eliminar categoría
            </span>

            <button
                type="button"
                class="cerrar-ventana"
                onclick="cerrarVentanaEliminar()">
                ✕
            </button>

        </div>

        <div class="ventana-eliminar-body">

            <h3 id="nombre-categoria-eliminar"></h3>

            <p id="mensaje-categoria-eliminar">
                Comprobando productos asociados...
            </p>

            <div id="productos-categoria-eliminar"></div>

        </div>

        <div class="ventana-eliminar-footer">

            <button
                type="button"
                class="btn-cancelar-ventana"
                onclick="cerrarVentanaEliminar()">
                Cancelar
            </button>

            <button
                type="button"
                id="btn-confirmar-eliminacion"
                class="btn-confirmar-eliminacion">
                Aceptar
            </button>

        </div>

    </div>

</div>

<script src="../dashboard/dashboard.js"></script>

<script>

function abrirVentanaEliminar(idCategoria, nombreCategoria) {


    const ventana =
        document.getElementById("ventana-eliminar-categoria");

    const nombre =
        document.getElementById("nombre-categoria-eliminar");

    const mensaje =
        document.getElementById("mensaje-categoria-eliminar");

    const productos =
        document.getElementById("productos-categoria-eliminar");

    const botonConfirmar =
        document.getElementById("btn-confirmar-eliminacion");


    nombre.textContent =
        "Categoría: " + nombreCategoria;

    mensaje.textContent =
        "Comprobando productos asociados...";

    productos.innerHTML = "";

    botonConfirmar.style.display = "none";

    ventana.style.display = "flex";

    /*
    =================================
        CONSULTAR PRODUCTOS
    =================================
    */

    fetch("obtener_productos_categoria.php?id=" + idCategoria)

        .then(response => response.json())

        .then(data => {

            if (!data.exito) {

                mensaje.textContent =
                    data.mensaje;

                return;

            }


            if (data.productos.length === 0) {

                mensaje.textContent =
                    "Esta categoría no tiene productos asociados. Puedes eliminarla.";

                botonConfirmar.style.display =
                    "inline-block";


                botonConfirmar.onclick =
                    function() {

                        if (confirm(
                            "¿Estás seguro de eliminar esta categoría?"
                        )) {

                            window.location.href =
                                "eliminar_categoria.php?id=" +
                                idCategoria;

                        }

                    };


                return;

            }


            mensaje.textContent =
                "Esta categoría tiene " +
                data.productos.length +
                " producto(s) asociado(s). Reasígnalos antes de eliminarla.";


            data.productos.forEach(function(producto) {

                const fila =
                    document.createElement("div");

                fila.className =
                    "producto-reasignar";


                const nombreProducto =
                    document.createElement("span");

                nombreProducto.textContent =
                    producto.nombre;


                const selector =
                    document.createElement("select");

                selector.dataset.producto =
                    producto.id_producto;


                const opcionInicial =
                    document.createElement("option");

                opcionInicial.value = "";

                opcionInicial.textContent =
                    "Seleccionar categoría...";

                opcionInicial.disabled = true;

                opcionInicial.selected = true;

                selector.appendChild(opcionInicial);


                <?php foreach ($categoriasDisponibles as $cat) { ?>

                    if (
                        <?php echo (int) $cat["id_categoria"]; ?> !==
                        idCategoria
                    ) {

                        const opcion =
                            document.createElement("option");

                        opcion.value =
                            "<?php echo (int) $cat["id_categoria"]; ?>";

                        opcion.textContent =
                            <?php echo json_encode($cat["nombre"]); ?>;

                        selector.appendChild(opcion);

                    }

                <?php } ?>


                fila.appendChild(nombreProducto);

                fila.appendChild(selector);

                productos.appendChild(fila);

            });


            /*
            =================================
                BOTÓN REORGANIZAR
            =================================
            */

            botonConfirmar.textContent =
                "💾 Reorganizar y eliminar";


            botonConfirmar.style.display =
                "inline-block";


            botonConfirmar.onclick =
                function() {

                    guardarReorganizacion(
                        idCategoria
                    );

                };

        })

        .catch(error => {

            console.error(error);

            mensaje.textContent =
                "No fue posible obtener los productos asociados.";

        });

}


function cerrarVentanaEliminar() {

    document.getElementById(
        "ventana-eliminar-categoria"
    ).style.display = "none";

}


function guardarReorganizacion(idCategoria) {

    const selectores =
        document.querySelectorAll(
            "#productos-categoria-eliminar select"
        );

    const cambios = [];

    let completo = true;


    /*
    =================================
        RECOPILAR CAMBIOS
    =================================
    */

    selectores.forEach(function(select) {

        if (select.value === "") {

            completo = false;

        }

        cambios.push({

            id_producto:
                select.dataset.producto,

            id_categoria:
                select.value

        });

    });


    /*
    =================================
        VERIFICAR QUE TODOS
        TENGAN CATEGORÍA
    =================================
    */

    if (!completo) {

        alert(
            "🛑 Debes seleccionar una nueva categoría para todos los productos."
        );

        return;

    }


    /*
    =================================
        CONFIRMAR OPERACIÓN
    =================================
    */

    if (!confirm(
        "¿Estás seguro de reorganizar los productos y eliminar esta categoría?"
    )) {

        return;

    }


    /*
    =================================
        ENVIAR DATOS A PHP
    =================================
    */

    fetch("reorganizar_categoria.php", {

        method: "POST",

        headers: {
            "Content-Type": "application/json"
        },

        body: JSON.stringify({

            id_categoria: idCategoria,

            cambios: cambios

        })

    })


    /*
    =================================
        PROCESAR RESPUESTA
    =================================
    */

    .then(response => response.json())

    .then(data => {

        if (data.exito) {

            alert(
                "✅ " + data.mensaje
            );

            window.location.href =
                "categorias.php";

        } else {

            alert(
                "🛑 " + data.mensaje
            );

        }

    })


    /*
    =================================
        ERROR DE CONEXIÓN
    =================================
    */

    .catch(error => {

        console.error(error);

        alert(
            "🛑 Ocurrió un error al comunicarse con el servidor."
        );

    });

}

</script>

</body>
</html>