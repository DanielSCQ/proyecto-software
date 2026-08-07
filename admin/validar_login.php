<?php

session_start();

require_once("../config/conexion.php");


if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $correo = trim($_POST["correo"]);
    $clave = trim($_POST["contrasena"]);


    if(empty($correo) || empty($clave)){

        header("Location:login.php?error=campos");
        exit();

    }


    $sql = "SELECT * FROM usuarios WHERE correo = ?";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param("s", $correo);

    $stmt->execute();

    $resultado = $stmt->get_result();



    if($resultado->num_rows == 1){


        $usuario = $resultado->fetch_assoc();


        if($clave == $usuario["clave"]){


            if($usuario["rol"] == "administrador"){


                $_SESSION["id_usuario"] = $usuario["id_usuario"];
                $_SESSION["nombre"] = $usuario["nombre"];
                $_SESSION["rol"] = $usuario["rol"];

                $_SESSION["intentos"]=0;

                header("Location:dashboard.php");exit();


                // Después aquí irá el dashboard
                // header("Location: dashboard.php");


            }else{

                $_SESSION["intentos"]++;

                if($_SESSION["intentos"]<3){
                    $_SESSION["correo"]=$correo;
                }else{
                    unset($_SESSION["correo"]);
                }

                header("Location:login.php?error=permisos");
                exit();

            }


        }else{

            $_SESSION["intentos"]++;

            if($_SESSION["intentos"]<3){
                    $_SESSION["correo"]=$correo;
                }else{
                    unset($_SESSION["correo"]);
                }

            header("Location:login.php?error=clave");
            exit();

        }


    }else{

        $_SESSION["intentos"]++;

        if($_SESSION["intentos"]<3){
                    $_SESSION["correo"]=$correo;
                }else{
                    unset($_SESSION["correo"]);
                }
                
        header("Location:login.php?error=correo");
        exit();

    }


}else{

    header("Location:login.php");
    exit();

}

?>