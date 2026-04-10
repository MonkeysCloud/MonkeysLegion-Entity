<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Mark a class as an entity to be scanned by EntityScanner.
 *
 * The optional `table` parameter specifies the database table name.
 * When omitted, MetadataRegistry auto-generates it from the class name
 * using snake_case pluralisation (e.g. `OrderItem` → `order_items`).
 *
 * ```php
 * #[Entity(table: 'users')]
 * class User {
 *     #[Id]
 *     #[Field(type: 'unsignedBigInt', autoIncrement: true)]
 *     public private(set) int $id;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Entity
{
    public function __construct(
        public readonly ?string $table = null,
    ) {}
}