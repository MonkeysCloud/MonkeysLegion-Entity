<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Mark a property as a UUID primary key.
 *
 * The entity's UUID generator hooks into this attribute to auto-generate
 * a UUID v4 value on INSERT when the property is uninitialized.
 *
 * ```php
 * #[Entity(table: 'events')]
 * class Event {
 *     #[Id]
 *     #[Uuid]
 *     #[Field(type: 'uuid')]
 *     public private(set) string $id;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Uuid {}
