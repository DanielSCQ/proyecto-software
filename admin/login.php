<?php

session_start();

$mensaje = "";

if (isset($_GET["error"])) {

    switch ($_GET["error"]) {

        case "campos":
            $mensaje = "⚠️ Complete todos los campos.";
            break;

        case "correo":
            $mensaje = "❌ El correo no está registrado.";
            break;

        case "clave":
            $mensaje = "❌ Contraseña incorrecta.";
            break;

        case "permisos":
            $mensaje = "🚫 No tiene permisos de administrador.";
            break;

            case "intentos":
            $mensaje = "🔒 Demasiados intentos fallidos. Por seguridad, vuelva a ingresar su correo.";
            break;

    }

}

if(!isset($_SESSION["intentos"])){
    $_SESSION["intentos"] = 0;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración | AGRANDA</title>

    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="contenedor-login">

    <!-- Lado izquierdo -->
    <section class="lado-izquierdo">

        <div class="logo-admin">
            <h1>AGRANDA</h1>
            <h2>Panel de Administración</h2>
        </div>

        <div class="banner-admin">
            <!-- Aquí irá la imagen -->
        </div>

        <div class="informacion-admin">
            <p>✓ Gestión de productos</p>
            <p>✓ Control de inventario</p>
            <p>✓ Administración de pedidos</p>
            <p>✓ Gestión de clientes</p>
            <p>✓ Configuración de la tienda</p>
        </div>

    </section>


    <!-- Lado derecho -->
    <section class="lado-derecho">

        <div class="formulario-login">

            <h2>Bienvenido</h2>

            <p>
                Ingrese sus credenciales para acceder al panel de administración.
            </p>

            <?php if (!empty($mensaje)) : ?>

            <div class="mensaje-error">
                <?php echo $mensaje; ?>
            </div>

            <?php endif; ?>

            <form action="validar_login.php" method="POST">

                <label>correo electronico</label>

                <input
                    type="email"
                    name="correo"
                    placeholder="Ingrese su correo elecronico"
                    value="<?php echo $_SESSION['correo']??'';?>"
                    required
                >

                <label>Contraseña</label>

                <input
                    type="password"
                    name="contrasena"
                    placeholder="Ingrese su contraseña"
                    required
                >

                <button type="submit">
                    Iniciar sesión
                </button>

            </form>

        </div>

    </section>

</div>


<footer>

    <p>
        © 2026 AGRANDA | Todos los derechos reservados
    </p>

</footer>

<script src="admin.js"></script>

</body>
</html>