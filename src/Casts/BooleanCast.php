<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Casts;

use MonkeysLegion\Entity\Contracts\CastInterface;

/**
 * MonKeysLegion Framework — Entity Package
 *
 * Casts between database boolean representations (0/1, true/false, t/f)
 * and PHP booleans.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class BooleanCast implements CastInterface
{
    private const array TRUE_VALUES = ['1', 1, 'true', 'True', 'TRUE', 't', 'T', 'yes', 'on', true];

    public function get(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array($value, self::TRUE_VALUES, true);
    }

    public function set(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        return (int) (bool) $value;
    }
}
