<?php

final class TestRunner {
    private int $correctas = 0;
    private int $fallidas = 0;

    public function prueba(string $nombre, callable $prueba): void {
        try {
            $prueba();
            $this->correctas++;
            echo '[OK] ' . $nombre . PHP_EOL;
        } catch (Throwable $e) {
            $this->fallidas++;
            echo '[FALLO] ' . $nombre . PHP_EOL;
            echo '        ' . $e->getMessage() . PHP_EOL;
        }
    }

    public static function verdadero(bool $condicion, string $mensaje = 'La condición no se cumplió.'): void {
        if (!$condicion) {
            throw new RuntimeException($mensaje);
        }
    }

    public static function igual($esperado, $actual, string $mensaje = ''): void {
        if ($esperado !== $actual) {
            $detalle = 'Esperado: ' . var_export($esperado, true)
                . '; recibido: ' . var_export($actual, true);
            throw new RuntimeException($mensaje !== '' ? $mensaje . ' ' . $detalle : $detalle);
        }
    }

    public static function lanza(string $clase, callable $accion): void {
        try {
            $accion();
        } catch (Throwable $e) {
            if ($e instanceof $clase) {
                return;
            }
            throw new RuntimeException(
                'Se esperaba ' . $clase . ' y se recibió ' . get_class($e)
            );
        }
        throw new RuntimeException('La operación debía lanzar ' . $clase . '.');
    }

    public static function proceso(array $comando, array $entorno = []): array {
        $descriptores = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $variablesActuales = getenv();
        $variables = $entorno
            ? array_merge(is_array($variablesActuales) ? $variablesActuales : [], $entorno)
            : null;
        $proceso = proc_open(
            $comando,
            $descriptores,
            $pipes,
            dirname(__DIR__),
            $variables,
            ['bypass_shell' => true]
        );
        if (!is_resource($proceso)) {
            throw new RuntimeException('No se pudo iniciar el proceso de prueba.');
        }
        fclose($pipes[0]);
        $salida = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $codigo = proc_close($proceso);
        return ['codigo' => $codigo, 'salida' => $salida, 'error' => $error];
    }

    public function finalizar(): int {
        echo PHP_EOL;
        echo 'Correctas: ' . $this->correctas . PHP_EOL;
        echo 'Fallidas: ' . $this->fallidas . PHP_EOL;
        return $this->fallidas === 0 ? 0 : 1;
    }
}
