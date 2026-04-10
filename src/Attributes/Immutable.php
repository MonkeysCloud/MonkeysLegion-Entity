<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Mark an entity as immutable — blocks UPDATE and DELETE after INSERT.
 *
 * Inspired by Domain-Driven Design value objects and Kotlin data classes.
 * Once persisted, the UnitOfWork and Repository layer refuse to update
 * or hard-delete the entity, throwing ImmutableEntityException.
 *
 * Perfect for: financial transactions, audit logs, event sourcing records.
 *
 * ```php
 * #[Entity(table: 'transactions')]
 * #[Immutable]
 * class Transaction {
 *     #[Id]
 *     #[Field(type: 'unsignedBigInt', autoIncrement: true)]
 *     public private(set) int $id;
 *
 *     #[Field(type: 'decimal', precision: 10, scale: 2)]
 *     public string $amount;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Immutable {}
