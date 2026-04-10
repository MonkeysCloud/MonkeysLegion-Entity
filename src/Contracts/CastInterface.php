<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Contracts;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Contract for custom value casters used with #[Cast].
 *
 * Implement this interface to define custom hydration/extraction logic
 * for entity properties — similar to Eloquent attribute casts but with
 * full type safety and access to the entity instance.
 *
 * ```php
 * class JsonCast implements CastInterface {
 *     public function get(mixed $value, string $attribute, object $entity): mixed {
 *         return json_decode((string) $value, true);
 *     }
 *     public function set(mixed $value, string $attribute, object $entity): mixed {
 *         return json_encode($value, JSON_THROW_ON_ERROR);
 *     }
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
interface CastInterface
{
    /**
     * Cast the value when reading from the database (hydration).
     */
    public function get(mixed $value, string $attribute, object $entity): mixed;

    /**
     * Cast the value when writing to the database (extraction).
     */
    public function set(mixed $value, string $attribute, object $entity): mixed;
}
