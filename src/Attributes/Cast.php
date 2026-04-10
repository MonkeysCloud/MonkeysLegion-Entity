<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Auto-cast property values during hydration and extraction.
 *
 * The cast pipeline runs BEFORE raw type coercion in the Hydrator.
 * Supports backed enums, DateTimeImmutable, scalar types, array
 * (JSON decode), and custom cast classes implementing CastInterface.
 *
 * ```php
 * #[Entity(table: 'orders')]
 * class Order {
 *     #[Field(type: 'string', length: 50)]
 *     #[Cast(OrderStatus::class)]
 *     public OrderStatus $status;
 *
 *     #[Field(type: 'json')]
 *     #[Cast('array')]
 *     public array $metadata = [];
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Cast
{
    public function __construct(
        public readonly string $castTo,
    ) {}
}
