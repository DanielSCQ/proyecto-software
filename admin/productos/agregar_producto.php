<?php

session_start();

// Si no ha iniciado sesión, vuelve al login
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once("../../config/conexion.php");

    $sqlCategorias = "SELECT id_categoria, nombre
                      FROM categorias
                      WHERE estado = 1
                      ORDER BY nombre ASC";

    $resultadoCategorias = $conexion->query($sqlCategorias);

    $sqlMarcas = "SELECT id_marca, nombre
              FROM marcas
              WHERE estado = 1
              ORDER BY nombre ASC";

    $resultadoMarcas = $conexion->query($sqlMarcas);

    $sqlProveedores = "SELECT id_proveedor, nombre
                   FROM proveedores
                   WHERE estado = 1
                   ORDER BY nombre ASC";

    $resultadoProveedores = $conexion->query($sqlProveedores);

    // Obtener atributos activos para las características de productos
    $sqlAtributos = "SELECT id_atributo, nombre
                    FROM atributos_producto
                    WHERE estado = TRUE
                    ORDER BY nombre ASC";

    $resultadoAtributos = $conexion->query($sqlAtributos);

    $atributos = [];

    if ($resultadoAtributos) {

        while ($filaAtributo = $resultadoAtributos->fetch_assoc()) {
            $atributos[] = $filaAtributo;
        }

    }

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de Control | AGRANDA</title>

    <link rel="stylesheet" href="productos.css">

</head>

<body>

    <!-- Contenedor principal -->
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

                    <li><a href="productos.php" class="activo">📦 Productos</a></li>

                    <li><a href="../categorias/categorias.php">🗂️ Categorías</a></li>

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

            <form action="acciones_productos.php" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="accion" value="agregar">

                <h2>➕ Agregar Producto</h2>

                <label>Nombre del producto</label>
                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    maxlength="150"
                    required>

                    <div id="contador-nombre"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                        0 / 150 caracteres
                    </div>    

                <label>Código del producto</label>
                <input
                    type="text"
                    name="codigo_producto"
                    id="codigo_producto"
                    maxlength="50"
                    required>

                    <div id="contador-codigo"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                        0 / 50 caracteres
                    </div>

                <label>Categoría</label>

                <select name="id_categoria" required>

                    <option value="">Seleccione una categoría</option>

                    <?php while($categoria = $resultadoCategorias->fetch_assoc()){ ?>

                        <option value="<?php echo $categoria["id_categoria"]; ?>">
                            <?php echo $categoria["nombre"]; ?>
                        </option>

                    <?php } ?>

                </select>


                <label>Marca</label>

                <select name="id_marca" required>

                    <option value="">Seleccione una marca</option>

                    <?php while($marca = $resultadoMarcas->fetch_assoc()){ ?>

                        <option value="<?php echo $marca["id_marca"]; ?>">
                            <?php echo $marca["nombre"]; ?>
                        </option>

                    <?php } ?>

                </select>

                <label>Proveedor</label>

                <select name="id_proveedor" required>

                    <option value="">Seleccione un proveedor</option>

                        <?php while($proveedor = $resultadoProveedores->fetch_assoc()){ ?>

                    <option value="<?php echo $proveedor["id_proveedor"]; ?>">
                        <?php echo $proveedor["nombre"]; ?>
                    </option>

                    <?php } ?>

                </select>

                <label>Código del proveedor</label>

                <input
                    type="text"
                    name="codigo_proveedor">

                <div class="fila">

                    <div class="campo">

                        <label>Precio de venta</label>

                        <input
                            type="number"
                            name="precio"
                            step="0.01"
                            min="0"
                            required>

                    </div>

                    <div class="campo">

                        <label>Precio de compra</label>

                        <input
                            type="number"
                            name="precio_compra"
                            step="0.01"
                            min="0"
                            required>

                    </div>
                    
                </div>

                <div class="fila">

                    <div class="campo">

                        <label>Stock inicial</label>

                        <input
                            type="number"
                            name="stock_actual"
                            min="0"
                            value="0"
                            required>

                    </div>

                    <div class="campo">

                        <label>Stock mínimo</label>

                        <input
                            type="number"
                            name="stock_minimo"
                            min="0"
                            value="0"
                            required>

                    </div>

                </div>                

                <div class="fila">

                    <div class="campo">

                        <label>Peso (kg)</label>

                        <input
                            type="number"
                            name="peso"
                            step="0.01"
                            min="0">

                    </div>

                </div>

                <label>Descripción</label>

                <textarea
                    name="descripcion"
                    id="descripcion"
                    maxlength="300"
                    rows="5"
                    placeholder="Descripción del producto..."
                ></textarea>

                <div
                    id="contador-descripcion"
                    style="text-align:right;margin-top:5px;color:#666;font-size:13px;">
                    0 / 300 caracteres
                </div>

                <!-- =================================
                    CARACTERÍSTICAS DEL PRODUCTO
                ================================= -->

                <div class="seccion-producto">

                    <h3 class="titulo-seccion-producto">
                        Características del producto
                    </h3>

                    <p class="ayuda-seccion-producto">
                        Agrega las características específicas del producto.
                    </p>

                    <div id="contenedor-caracteristicas">

                        <div class="fila-caracteristica">

                            <div class="campo">
                                <label for="atributo_0">Característica</label>

                                <select 
                                    name="atributo_id[]" 
                                    id="atributo_0"
                                    class="select-atributo"
                                >
                                    <option value="">Seleccione una característica</option>

                                    <?php foreach ($atributos as $atributo): ?>

                                        <option value="<?= (int) $atributo["id_atributo"] ?>">
                                            <?= htmlspecialchars($atributo["nombre"]) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="campo">
                                <label for="valor_atributo_0">Valor</label>

                                <input
                                    type="text"
                                    name="atributo_valor[]"
                                    id="valor_atributo_0"
                                    maxlength="300"
                                    placeholder="Ej: Acero inoxidable"
                                >

                                <div class="contador-campo">
                                    <span class="contador-valor">0</span> / 300
                                </div>
                            </div>

                            <button 
                                type="button"
                                class="btn-eliminar-caracteristica"
                                onclick="eliminarCaracteristica(this)"
                                aria-label="Eliminar característica"
                            >
                                ×
                            </button>

                        </div>

                    </div>

                    <button 
                        type="button"
                        id="btn-agregar-caracteristica"
                        class="btn-agregar-caracteristica"
                    >
                        + Agregar característica
                    </button>

                </div>

                <label>Imagen del producto</label>

                <input
                    type="file"
                    name="imagen"
                    accept="image/*">

                <label>Estado</label>

                <select name="estado">

                    <option value="1">Activo</option>

                    <option value="0">Inactivo</option>

                </select>

                <label>Producto destacado</label>

                <select name="destacado">

                    <option value="0">No</option>

                    <option value="1">Sí</option>

                </select>

                <br><br>

                <button
                    type="submit"
                    class="btn-nuevo">

                    💾 Guardar Producto

                </button>

                <a
                    href="productos.php"
                    class="btn-cancelar">

                    Cancelar

                </a>

            </form>
        </div>    
        </main>

    </div>

    <script src="../dashboard/dashboard.js"></script>
<script>

const nombre = document.getElementById("nombre");
const contadorNombre = document.getElementById("contador-nombre");

const codigo = document.getElementById("codigo_producto");
const contadorCodigo = document.getElementById("contador-codigo");

const descripcion = document.getElementById("descripcion");
const contadorDescripcion = document.getElementById("contador-descripcion");


/*
=================================
    CONTADOR DEL NOMBRE
=================================
*/

function actualizarContadorNombre() {

    const cantidad = nombre.value.length;

    contadorNombre.textContent = cantidad + " / 150 caracteres";

    if (cantidad >= 150) {

        contadorNombre.style.color = "#c62828";
        contadorNombre.style.fontWeight = "bold";

    } else {

        contadorNombre.style.color = "#666";
        contadorNombre.style.fontWeight = "normal";

    }

}


/*
=================================
    CONTADOR DEL CÓDIGO
=================================
*/

function actualizarContadorCodigo() {

    const cantidad = codigo.value.length;

    contadorCodigo.textContent = cantidad + " / 50 caracteres";

    if (cantidad >= 50) {

        contadorCodigo.style.color = "#c62828";
        contadorCodigo.style.fontWeight = "bold";

    } else {

        contadorCodigo.style.color = "#666";
        contadorCodigo.style.fontWeight = "normal";

    }

}

/*
=================================
    CONTADOR DE LA DESCRIPCIÓN
=================================
*/

function actualizarContadorDescripcion() {

    const cantidad = descripcion.value.length;

    contadorDescripcion.textContent =
        cantidad + " / 300 caracteres";

    if (cantidad >= 300) {

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

codigo.addEventListener("input", actualizarContadorCodigo);

descripcion.addEventListener("input", actualizarContadorDescripcion);

/*
=================================
    ACTUALIZAR AL CARGAR
=================================
*/
actualizarContadorNombre();
actualizarContadorCodigo();
actualizarContadorDescripcion();

let contadorCaracteristicas = 1;

document.getElementById("btn-agregar-caracteristica").addEventListener("click", function () {

    const contenedor = document.getElementById("contenedor-caracteristicas");

    const fila = document.createElement("div");

    fila.className = "fila-caracteristica";

    fila.innerHTML = `
        <div class="campo">
            <label for="atributo_${contadorCaracteristicas}">
                Característica
            </label>

            <select
                name="atributo_id[]"
                id="atributo_${contadorCaracteristicas}"
                class="select-atributo"
            >
                <option value="">Seleccione una característica</option>

                <?php foreach ($atributos as $atributo): ?>

                    <option value="<?= (int) $atributo["id_atributo"] ?>">
                        <?= htmlspecialchars($atributo["nombre"]) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <div class="campo">
            <label for="valor_atributo_${contadorCaracteristicas}">
                Valor
            </label>

            <input
                type="text"
                name="atributo_valor[]"
                id="valor_atributo_${contadorCaracteristicas}"
                maxlength="300"
                placeholder="Ej: Acero inoxidable"
            >

            <div class="contador-campo">
                <span class="contador-valor">0</span> / 300
            </div>
        </div>

        <button
            type="button"
            class="btn-eliminar-caracteristica"
            onclick="eliminarCaracteristica(this)"
            aria-label="Eliminar característica"
        >
            ×
        </button>
    `;

    contenedor.appendChild(fila);

    const inputValor = fila.querySelector(".contador-valor");

    const input = fila.querySelector('input[name="atributo_valor[]"]');

    input.addEventListener("input", function () {

        inputValor.textContent = this.value.length;

        if (this.value.length >= 300) {
            inputValor.style.color = "red";
            inputValor.style.fontWeight = "bold";
        } else {
            inputValor.style.color = "";
            inputValor.style.fontWeight = "";
        }

    });

    contadorCaracteristicas++;

});

function eliminarCaracteristica(boton) {

    const fila = boton.closest(".fila-caracteristica");

    if (fila) {
        fila.remove();
    }

}

</script>
</body>
</html>