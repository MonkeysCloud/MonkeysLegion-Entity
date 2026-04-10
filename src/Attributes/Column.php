<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Annotate a property with explicit column metadata.
 *
 * Use this when the database column name differs from the PHP property
 * name, or to specify SQL-level type overrides.
 *
 * ```php
 * #[Entity(table: 'users')]
 * class User {
 *     #[Column(name: 'user_email')]
 *     #[Field(type: 'string', length: 255)]
 *     public string $email;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Column
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?int $length = null,
        public readonly bool $nullable = false,
    ) {}
}