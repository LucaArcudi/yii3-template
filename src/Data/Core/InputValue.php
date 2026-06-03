<?php

declare(strict_types=1);

namespace App\Data\Core;

use function is_scalar;
use function ltrim;
use function preg_match;
use function strlen;
use function str_starts_with;
use function trim;

final class InputValue
{
    public const DB_INT_SIGNED_MAX = 2147483647;
    public const DB_INT_UNSIGNED_MAX = 4294967295;

    public static function intOrBoundary(
        mixed $value,
        int $min = 0,
        int $max = self::DB_INT_SIGNED_MAX,
    ): ?int {
        if (!is_scalar($value)) {
            return self::below($min);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^[+-]?\d+$/', $value) !== 1) {
            return self::below($min);
        }

        $negative = str_starts_with($value, '-');
        $digits = ltrim($value, '+-');
        $digits = ltrim($digits, '0');

        if ($digits === '') {
            $digits = '0';
            $negative = false;
        }

        if ($negative) {
            return self::below($min);
        }

        if (self::compareUnsigned($digits, (string) $min) < 0) {
            return self::below($min);
        }

        if (self::compareUnsigned($digits, (string) $max) > 0) {
            return self::above($max);
        }

        return (int) $digits;
    }

    public static function inRange(?int $value, int $min = 0, int $max = self::DB_INT_SIGNED_MAX): bool
    {
        return $value !== null && $value >= $min && $value <= $max;
    }

    private static function below(int $min): int
    {
        return $min - 1;
    }

    private static function above(int $max): int
    {
        return $max + 1;
    }

    private static function compareUnsigned(string $left, string $right): int
    {
        $left = ltrim($left, '0');
        $right = ltrim($right, '0');

        $left = $left === '' ? '0' : $left;
        $right = $right === '' ? '0' : $right;

        $leftLength = strlen($left);
        $rightLength = strlen($right);

        if ($leftLength !== $rightLength) {
            return $leftLength <=> $rightLength;
        }

        return $left <=> $right;
    }

    private function __construct()
    {
    }
}
