<?php

/**
 * Cifrado autenticado para claves de producto y secretos de licencias.
 * La llave maestra solo se obtiene de APP_ENCRYPTION_KEY y nunca de la BD.
 */
final class SecretoLicencia {
    private const CIFRADO = 'aes-256-gcm';
    private const PREFIJO = 'v1:';
    private const AAD = 'GestActivos/licencias/v1';
    private const NONCE_BYTES = 12;
    private const TAG_BYTES = 16;

    public static function disponible(): bool {
        try {
            self::llave();
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    public static function cifrar(string $secreto): string {
        $secreto = trim($secreto);
        if ($secreto === '') {
            throw new RuntimeException('La clave de licencia no puede estar vacía.');
        }
        $nonce = random_bytes(self::NONCE_BYTES);
        $tag = '';
        $cifrado = openssl_encrypt(
            $secreto,
            self::CIFRADO,
            self::llave(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            self::AAD,
            self::TAG_BYTES
        );
        if ($cifrado === false || strlen($tag) !== self::TAG_BYTES) {
            throw new RuntimeException('No se pudo proteger la clave de licencia.');
        }
        return self::PREFIJO . base64_encode($nonce . $tag . $cifrado);
    }

    public static function descifrar(string $protegido): string {
        if (!str_starts_with($protegido, self::PREFIJO)) {
            throw new RuntimeException('El formato de la clave protegida no es válido.');
        }
        $binario = base64_decode(substr($protegido, strlen(self::PREFIJO)), true);
        if ($binario === false || strlen($binario) <= self::NONCE_BYTES + self::TAG_BYTES) {
            throw new RuntimeException('La clave protegida está incompleta.');
        }
        $nonce = substr($binario, 0, self::NONCE_BYTES);
        $tag = substr($binario, self::NONCE_BYTES, self::TAG_BYTES);
        $cifrado = substr($binario, self::NONCE_BYTES + self::TAG_BYTES);
        $plano = openssl_decrypt(
            $cifrado,
            self::CIFRADO,
            self::llave(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            self::AAD
        );
        if ($plano === false) {
            throw new RuntimeException('No se pudo verificar o descifrar la clave de licencia.');
        }
        return $plano;
    }

    public static function mascara(string $secreto): string {
        $limpio = preg_replace('/\s+/', '', trim($secreto)) ?? '';
        if ($limpio === '') {
            return '';
        }
        $ultimos = function_exists('mb_substr')
            ? mb_substr($limpio, -4, null, 'UTF-8')
            : substr($limpio, -4);
        return '••••-••••-' . $ultimos;
    }

    public static function huella(string $secreto): string {
        $normalizado = mb_strtoupper(
            preg_replace('/[\s-]+/u', '', trim($secreto)) ?? '',
            'UTF-8'
        );
        if ($normalizado === '') {
            throw new RuntimeException('No se puede calcular la huella de una clave vacía.');
        }
        return hash_hmac('sha256', $normalizado, self::llave());
    }

    private static function llave(): string {
        $configurada = trim((string)APP_ENCRYPTION_KEY);
        if ($configurada === '') {
            throw new RuntimeException('Configura APP_ENCRYPTION_KEY antes de guardar claves de licencia.');
        }
        if (preg_match('/^[a-f0-9]{64}$/i', $configurada)) {
            $llave = hex2bin($configurada);
        } else {
            $llave = base64_decode($configurada, true);
        }
        if (!is_string($llave) || strlen($llave) !== 32) {
            throw new RuntimeException('APP_ENCRYPTION_KEY debe representar exactamente 32 bytes.');
        }
        return $llave;
    }
}
