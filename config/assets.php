<?php

/*
=========================================
 VERSIONADO AUTOMÁTICO DE CSS Y JS
 AGRANDA
=========================================
*/

if (!function_exists("agranda_version_asset")) {

    function agranda_version_asset(
        string $rutaPublica,
        string $directorioFisicoBase
    ): string {

        /*
        -----------------------------------------
        NO MODIFICAR RECURSOS EXTERNOS
        -----------------------------------------
        */

        if (
            preg_match('#^([a-z]+:)?//#i', $rutaPublica) ||
            str_starts_with($rutaPublica, "data:")
        ) {
            return $rutaPublica;
        }


        /*
        -----------------------------------------
        SEPARAR QUERY STRING SI YA EXISTE
        -----------------------------------------
        */

        [$rutaSinQuery, $queryExistente] =
            array_pad(
                explode("?", $rutaPublica, 2),
                2,
                ""
            );


        /*
        -----------------------------------------
        CONSTRUIR RUTA FÍSICA
        -----------------------------------------
        */

        $rutaFisica =
            rtrim($directorioFisicoBase, "/\\") .
            "/" .
            ltrim($rutaSinQuery, "/");


        /*
        -----------------------------------------
        VERSIÓN DE RESPALDO ESTABLE
        -----------------------------------------
        */

        static $fallback = null;

        if ($fallback === null) {

            $fallback = is_file(__FILE__)
                ? filemtime(__FILE__)
                : 1;
        }


        /*
        -----------------------------------------
        OBTENER VERSIÓN DEL ARCHIVO
        -----------------------------------------
        */

        if (is_file($rutaFisica)) {

            $version = filemtime($rutaFisica);

        } else {

            $version = $fallback;
        }


        /*
        -----------------------------------------
        CONSERVAR PARÁMETROS EXISTENTES
        -----------------------------------------
        */

        $query =
            $queryExistente !== ""
                ? $queryExistente . "&v=" . $version
                : "v=" . $version;


        return $rutaSinQuery . "?" . $query;
    }
}


/*
=========================================
 ASSETS DE LA TIENDA
=========================================
*/

if (!function_exists("v_tienda")) {

    function v_tienda(string $rutaRelativa): string {

        return agranda_version_asset(
            $rutaRelativa,
            dirname(__DIR__) . "/tienda"
        );
    }
}


/*
=========================================
 ASSETS DEL ADMIN
=========================================
*/

if (!function_exists("v_admin")) {

    function v_admin(
        string $rutaRelativa,
        string $directorioDelArchivo
    ): string {

        return agranda_version_asset(
            $rutaRelativa,
            $directorioDelArchivo
        );
    }
}