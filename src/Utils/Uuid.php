<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Utils;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * UUID v4 generator and validator utility.
 *
 * Used by the entity layer to auto-generate UUID primary keys when
 * a property is marked with #[Uuid].
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class Uuid
{
    /**
     * Generate a cryptographically secure random UUID v4.
     */
    public static function v4(): string
    {
        $data = random_bytes(16);

        // Set version to 0100 (UUID v4)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Validate whether a string is a valid UUID format.
     */
    public static function isValid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid,
        );
    }
}
