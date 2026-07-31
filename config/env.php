<?php

/**
 * Carga opcional de .env sin dependencias externas.
 *
 * Las variables definidas por Apache, Docker o el sistema operativo siempre
 * tienen prioridad sobre el archivo local.
 */
$archivoEntorno = dirname(__DIR__) . '/.env';
if (is_readable($archivoEntorno)) {
    $variablesLocales = parse_ini_file($archivoEntorno, false, INI_SCANNER_RAW);
    if (is_array($variablesLocales)) {
        foreach ($variablesLocales as $nombre => $valor) {
            if (getenv($nombre) === false) {
                putenv($nombre . '=' . $valor);
                $_ENV[$nombre] = $valor;
            }
        }
    }
}

if (!function_exists('gestEnv')) {
    function gestEnv(string $nombre, $predeterminado = null) {
        $valor = getenv($nombre);
        return $valor === false || $valor === '' ? $predeterminado : $valor;
    }
}
