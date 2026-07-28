<?php
// ============================================================
// GestActivos - Lectura segura y consistente de filtros de tabla
// ============================================================

final class TableFilter
{
    public static function text(string $key, int $maxLength = 150, ?array $source = null): string
    {
        $source ??= $_POST;
        $value = $source[$key] ?? '';
        if (!is_string($value)) {
            return '';
        }

        $value = trim(str_replace("\0", '', $value));
        if ($maxLength < 1) {
            return '';
        }

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength, 'UTF-8')
            : substr($value, 0, $maxLength);
    }

    public static function enum(string $key, array $allowed, ?array $source = null): string
    {
        $value = self::text($key, 60, $source);
        $allowed = array_map('strval', $allowed);
        return in_array($value, $allowed, true) ? $value : '';
    }

    public static function positiveInt(string $key, ?array $source = null): int
    {
        $value = self::text($key, 12, $source);
        return ctype_digit($value) ? max(0, (int)$value) : 0;
    }

    public static function date(string $key, ?array $source = null): string
    {
        $value = self::text($key, 10, $source);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : '';
    }
}
