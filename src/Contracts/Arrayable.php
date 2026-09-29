<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Contracts;

/**
 * MonKeysLegion Framework — Entity Package
 *
 * Contract for objects that can be converted to an array.
 * Used by API resources, template rendering, and serialization.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
interface Arrayable
{
    /**
     * Convert the object to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
