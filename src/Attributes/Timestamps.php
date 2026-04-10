<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Auto-manage created_at / updated_at timestamps.
 *
 * The Hydrator and UnitOfWork automatically populate these columns:
 *  • `created_at` is set on first INSERT
 *  • `updated_at` is refreshed on every UPDATE
 *
 * Column names are configurable via constructor parameters.
 *
 * ```php
 * #[Entity(table: 'orders')]
 * #[Timestamps]
 * class Order {
 *     #[Field(type: 'datetime')]
 *     public private(set) \DateTimeImmutable $created_at;
 *
 *     #[Field(type: 'datetime')]
 *     public private(set) \DateTimeImmutable $updated_at;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Timestamps
{
    public function __construct(
        public readonly string $createdColumn = 'created_at',
        public readonly string $updatedColumn = 'updated_at',
    ) {}
}
