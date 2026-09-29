<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Casts;

use MonkeysLegion\Entity\Contracts\CastInterface;

/**
 * MonKeysLegion Framework — Entity Package
 *
 * Casts between database decimal strings and PHP floats.
 * Preserves precision on write via number_format.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class DecimalCast implements CastInterface
{
    public function __construct(
        private readonly int $scale = 2,
    ) {}

    public function get(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        return (float) $value;
    }

    public function set(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return $value;
        }

        return number_format((float) $value, $this->scale, '.', '');
    }
}
