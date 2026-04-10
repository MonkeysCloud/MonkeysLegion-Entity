<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Exclude this property from toArray() / toJson() serialization.
 *
 * Properties marked with #[Hidden] are omitted when the entity is
 * serialized for API responses or logging, preventing sensitive data
 * from leaking (passwords, internal notes, tokens, etc.).
 *
 * ```php
 * #[Entity(table: 'users')]
 * class User {
 *     #[Field(type: 'string')]
 *     #[Hidden]
 *     public string $password_hash;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Hidden {}
