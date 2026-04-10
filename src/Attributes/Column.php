<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Map a PHP property to a database column with a different name.
 *
 * When the database column name differs from the PHP property name,
 * use #[Column] to define the mapping. The Hydrator and extract()
 * methods use this to translate between DB rows and entity properties.
 *
 * For type, length, and nullable configuration, use #[Field] instead.
 *
 * ```php
 * #[Entity(table: 'users')]
 * class User {
 *     #[Column(name: 'user_email')]
 *     #[Field(type: 'string', length: 255)]
 *     public string $email;  // DB column = user_email, PHP prop = email
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Column
{
    /**
     * @param string $name The database column name.
     */
    public function __construct(
        public readonly string $name,
    ) {}
}