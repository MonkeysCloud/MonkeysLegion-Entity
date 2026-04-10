<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Define a database index on this entity or property.
 *
 * Can be applied at class level (composite indexes) or property level
 * (single-column indexes). Repeatable so multiple indexes per entity
 * are supported.
 *
 * ```php
 * #[Entity(table: 'orders')]
 * #[Index(columns: ['user_id', 'status'], name: 'idx_user_status')]
 * #[Index(columns: ['created_at'])]
 * class Order {
 *     #[Field(type: 'string')]
 *     #[Index(unique: true)]
 *     public string $order_number;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class Index
{
    /**
     * @param list<string> $columns Column names (empty = use property name).
     * @param string|null  $name    Optional index name (auto-generated if null).
     * @param bool         $unique  Whether to create a UNIQUE index.
     */
    public function __construct(
        public readonly array $columns = [],
        public readonly ?string $name = null,
        public readonly bool $unique = false,
    ) {}
}
