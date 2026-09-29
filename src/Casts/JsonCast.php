<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Casts;

use MonkeysLegion\Entity\Contracts\CastInterface;

/**
 * MonKeysLegion Framework — Entity Package
 *
 * Casts between JSON strings and PHP arrays/objects.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class JsonCast implements CastInterface
{
    public function get(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return $value;
        }

        try {
            return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    public function set(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            // Validate that the string is valid JSON; pass through if so.
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
