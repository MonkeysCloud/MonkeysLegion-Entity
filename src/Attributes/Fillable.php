<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Allow mass assignment for this property.
 *
 * When any property on an entity carries #[Fillable], the
 * MassAssignmentGuard switches to whitelist mode: only properties
 * explicitly marked #[Fillable] can be set via fill() / fromArray().
 *
 * ```php
 * #[Entity(table: 'users')]
 * class User {
 *     #[Field(type: 'string')]
 *     #[Fillable]
 *     public string $name;
 *
 *     #[Field(type: 'string')]
 *     public private(set) string $role; // not fillable
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Fillable {}
