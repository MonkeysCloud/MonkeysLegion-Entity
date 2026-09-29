<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Contracts;

/**
 * MonKeysLegion Framework — Entity Package
 *
 * Contract for objects that can be serialized to JSON.
 * Used by API responses and JSON log contexts.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
interface Jsonable
{
    /**
     * Convert the object to a JSON string.
     *
     * @param int $flags JSON encoding flags (e.g. JSON_PRETTY_PRINT).
     */
    public function toJson(int $flags = 0): string;
}
