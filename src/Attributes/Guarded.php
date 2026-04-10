<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Block mass assignment for this property.
 *
 * When no #[Fillable] attributes exist on the entity, the guard uses
 * blacklist mode: all properties are assignable EXCEPT those marked
 * #[Guarded]. This prevents accidental overwrites of sensitive fields
 * like `role`, `is_admin`, or `balance`.
 *
 * ```php
 * #[Entity(table: 'users')]
 * class User {
 *     #[Field(type: 'string')]
 *     #[Guarded]
 *     public string $role = 'user'; // cannot be mass-assigned
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Guarded {}
