<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Declare that this entity supports soft deletion.
 *
 * Instead of physically removing rows, a soft-deleted entity sets a
 * timestamp on the configured column. The query layer automatically
 * filters soft-deleted rows unless explicitly included.
 *
 * ```php
 * #[Entity(table: 'posts')]
 * #[SoftDeletes]
 * class Post {
 *     #[Field(type: 'datetime', nullable: true)]
 *     public ?\DateTimeImmutable $deleted_at = null;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class SoftDeletes
{
    public function __construct(
        public readonly string $column = 'deleted_at',
    ) {}
}
