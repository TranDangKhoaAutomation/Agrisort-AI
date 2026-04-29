<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $items = [];

    public static function setAll(array $config): void
    {
        self::$items = $config;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
