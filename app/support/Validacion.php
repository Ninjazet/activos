<?php

/**
 * Normalizacion y validacion comun para entradas de formularios.
 */
final class Validacion {
    public static function enteroPositivo($valor, string $campo): int {
        if (filter_var($valor, FILTER_VALIDATE_INT) === false || (int)$valor <= 0) {
            throw new RuntimeException($campo . ' no es válido.');
        }
        return (int)$valor;
    }

    public static function enteroPositivoOpcional($valor, string $campo): ?int {
        if ($valor === null || $valor === '' || $valor === '0' || $valor === 0) {
            return null;
        }
        return self::enteroPositivo($valor, $campo);
    }

    public static function textoOpcional($valor, int $maximo, string $campo): ?string {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (!is_string($valor)) {
            throw new RuntimeException($campo . ' no tiene un formato válido.');
        }
        $texto = trim($valor);
        if ($texto === '') {
            return null;
        }
        if (mb_strlen($texto) > $maximo) {
            throw new RuntimeException($campo . ' supera el tamaño permitido.');
        }
        return $texto;
    }

    public static function correoOpcional($valor): ?string {
        $correo = self::textoOpcional($valor, 150, 'El correo');
        if ($correo !== null && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Ingresa un correo electrónico válido.');
        }
        return $correo !== null ? mb_strtolower($correo, 'UTF-8') : null;
    }

    public static function fechaOpcional($valor, string $campo): ?string {
        $fecha = self::textoOpcional($valor, 10, $campo);
        if ($fecha === null) {
            return null;
        }
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        $errores = DateTimeImmutable::getLastErrors();
        $invalida = $objeto === false
            || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))
            || $objeto->format('Y-m-d') !== $fecha;
        if ($invalida) {
            throw new RuntimeException($campo . ' no es una fecha válida.');
        }
        return $fecha;
    }

    public static function costoOpcional($valor): ?float {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_array($valor) || !is_numeric($valor) || (float)$valor < 0) {
            throw new RuntimeException('El costo debe ser un número mayor o igual a cero.');
        }
        return round((float)$valor, 2);
    }

    public static function numeroSerieOpcional($valor): ?string {
        $serie = self::textoOpcional($valor, 100, 'El número de serie');
        return $serie !== null ? mb_strtoupper($serie, 'UTF-8') : null;
    }

    public static function validarOrdenFechas(?string $inicio, ?string $fin): void {
        if ($inicio !== null && $fin !== null && $fin < $inicio) {
            throw new RuntimeException('El vencimiento de garantía no puede ser anterior a la fecha de compra.');
        }
    }
}
