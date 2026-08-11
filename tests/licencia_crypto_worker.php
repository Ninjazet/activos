<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

try {
    if (!SecretoLicencia::disponible()) {
        throw new RuntimeException('La llave de prueba no quedó disponible.');
    }
    $secreto = 'ABCD-EFGH-IJKL-9876';
    $cifrado = SecretoLicencia::cifrar($secreto);
    $alterado = substr($cifrado, 0, -1) . ($cifrado[-1] === 'A' ? 'B' : 'A');
    $rechazoAlteracion = false;
    try {
        SecretoLicencia::descifrar($alterado);
    } catch (RuntimeException $e) {
        $rechazoAlteracion = true;
    }
    echo json_encode([
        'cifrado' => $cifrado,
        'descifrado' => SecretoLicencia::descifrar($cifrado),
        'mascara' => SecretoLicencia::mascara($secreto),
        'huella_igual' => hash_equals(
            SecretoLicencia::huella($secreto),
            SecretoLicencia::huella('abcd efgh ijkl 9876')
        ),
        'rechazo_alteracion' => $rechazoAlteracion,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception) . ': ' . $exception->getMessage());
    exit(1);
}
