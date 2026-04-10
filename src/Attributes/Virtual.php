<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Mark a property as virtual/computed — not persisted to the database.
 *
 * Inspired by Ecto (Elixir) virtual fields and Prisma computed fields.
 * The property exists in the PHP entity and is available in toArray()/toJson()
 * but is automatically skipped during INSERT, UPDATE, and extract().
 *
 * Best combined with PHP 8.4 property hooks for computed values:
 *
 * ```php
 * #[Entity(table: 'orders')]
 * class Order {
 *     #[Field(type: 'decimal')]
 *     public string $subtotal;
 *
 *     #[Field(type: 'decimal')]
 *     public string $tax;
 *
 *     #[Virtual]
 *     public string $total {
 *         get => bcadd($this->subtotal, $this->tax, 2);
 *     }
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Virtual {}
