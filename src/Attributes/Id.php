<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Mark a property as the primary key.
 *
 * Typically combined with #[Field] to specify the column type.
 * The MetadataRegistry records this as `primaryKey` in EntityMetadata.
 *
 * ```php
 * #[Entity(table: 'orders')]
 * class Order {
 *     #[Id]
 *     #[Field(type: 'unsignedBigInt', autoIncrement: true)]
 *     public private(set) int $id;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Id {}