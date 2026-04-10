<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Auto-add shadow audit columns that exist in the database but not
 * as PHP properties on the entity.
 *
 * Inspired by EF Core shadow properties. The columns are automatically
 * populated from the request context (authenticated user, IP address)
 * during INSERT and UPDATE operations without polluting the domain model.
 *
 * Shadow columns are queryable via the QueryBuilder but invisible to
 * toArray(), toJson(), and the entity's PHP property list.
 *
 * ```php
 * #[Entity(table: 'users')]
 * #[AuditTrail]
 * class User {
 *     // DB has created_by, updated_by, created_ip, updated_ip columns
 *     // but they are NOT declared as PHP properties
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AuditTrail
{
    public function __construct(
        public readonly string $createdByColumn = 'created_by',
        public readonly string $updatedByColumn = 'updated_by',
        public readonly string $createdIpColumn = 'created_ip',
        public readonly string $updatedIpColumn = 'updated_ip',
    ) {}
}
