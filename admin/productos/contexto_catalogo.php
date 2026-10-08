<?php
// Solo parámetros del catálogo; nunca aceptar una URL de retorno del navegador.
function agranda_catalogo_contexto(array $entrada): array
{
    $resultado = [];
    foreach (['q', 'estado', 'categoria', 'marca', 'pagina'] as $campo) {
        $valor = $entrada['catalogo_' . $campo] ?? '';
        if (!is_string($valor)) throw new InvalidArgumentException('Contexto del catálogo inválido.');
        if ($valor === '') continue;
        if ($campo === 'q') {
            if (!mb_check_encoding($valor, 'UTF-8') || mb_strlen($valor, 'UTF-8') > 200) {
                throw new InvalidArgumentException('Búsqueda de retorno inválida.');
            }
            $valor = trim($valor);
        } elseif ($campo === 'estado') {
            if (!in_array($valor, ['0', '1'], true)) throw new InvalidArgumentException('Estado de retorno inválido.');
        } elseif (!preg_match('/^[1-9][0-9]{0,9}$/D', $valor)
            || filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false) {
            throw new InvalidArgumentException('Identificador de retorno inválido.');
        }
        if ($valor !== '') $resultado[$campo] = $valor;
    }
    return $resultado;
}
function agranda_catalogo_contexto_enlace(array $filtros): array
{
    $resultado = [];
    foreach (['q', 'estado', 'categoria', 'marca', 'pagina'] as $campo) {
        if (isset($filtros[$campo])) $resultado['catalogo_' . $campo] = (string) $filtros[$campo];
    }
    return $resultado;
}
