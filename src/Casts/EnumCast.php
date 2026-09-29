<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Casts;

use MonkeysLegion\Entity\Contracts\CastInterface;
use BackedEnum;
use UnitEnum;
use ValueError;

/**
 * MonKeysLegion Framework — Entity Package
 *
 * Casts between backed enum instances and their scalar values.
 *
 * Supports both int-backed and string-backed enums.
 * Returns null for null values (use nullable property types).
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class EnumCast implements CastInterface
{
    /** @param class-string<BackedEnum> $enumClass */
    public function __construct(
        private readonly string $enumClass,
    ) {}

    public function get(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UnitEnum) {
            return $value;
        }

        try {
            return ($this->enumClass)::from($value);
        } catch (ValueError) {
            return null;
        }
    }

    public function set(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
    }
}
