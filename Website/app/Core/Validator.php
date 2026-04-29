<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    public static function required(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            $value = $data[$field] ?? null;
            if ($value === null || (is_string($value) && trim($value) === '')) {
                $errors[$field] = 'Field is required.';
            }
        }
        return $errors;
    }

    public static function email(?string $email): bool
    {
        return $email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function intValue(mixed $value, int $min = 0): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        return (int) $value >= $min;
    }
}
