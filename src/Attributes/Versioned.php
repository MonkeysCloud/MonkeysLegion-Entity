<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Enable optimistic locking on this property.
 *
 * Inspired by JPA/Hibernate @Version. The version column is auto-incremented
 * on every UPDATE and the WHERE clause includes `AND version = ?`. If the
 * version in the database has changed since the entity was loaded, an
 * OptimisticLockException is thrown — preventing lost-update anomalies.
 *
 * ```php
 * #[Entity(table: 'orders')]
 * class Order {
 *     #[Versioned]
 *     #[Field(type: 'integer')]
 *     public private(set) int $version = 1;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Versioned {}
