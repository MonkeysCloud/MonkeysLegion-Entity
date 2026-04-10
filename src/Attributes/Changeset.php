<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Define contextual mass-assignment and validation rules.
 *
 * Inspired by Ecto (Elixir) changesets. Different contexts allow
 * different writable fields on the same entity — the method returns
 * the list of field names permitted for that context.
 *
 * ```php
 * #[Entity(table: 'users')]
 * class User {
 *     #[Field(type: 'string')]
 *     public string $email;
 *
 *     #[Field(type: 'string')]
 *     public string $password;
 *
 *     #[Field(type: 'string')]
 *     public string $name;
 *
 *     #[Changeset(context: 'registration')]
 *     public static function registrationRules(): array {
 *         return ['email', 'password', 'name'];
 *     }
 *
 *     #[Changeset(context: 'profile_update')]
 *     public static function profileRules(): array {
 *         return ['name'];
 *     }
 * }
 *
 * // Usage:
 * MassAssignmentGuard::fill($user, $data, context: 'profile_update');
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Changeset
{
    public function __construct(
        public readonly string $context,
    ) {}
}
