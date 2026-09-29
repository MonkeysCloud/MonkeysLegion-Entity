<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Casts;

use MonkeysLegion\Entity\Contracts\CastInterface;

/**
 * MonKeysLegion Framework — Entity Package
 *
 * Casts between comma-separated strings (or JSON) and PHP arrays.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class ArrayCast implements CastInterface
{
    public function get(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        // Try JSON first
        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fall back to comma-separated string
        return array_map('trim', explode(',', (string) $value));
    }

    public function set(mixed $value, string $attribute, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
