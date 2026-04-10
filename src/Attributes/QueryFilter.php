<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Define a global query filter auto-applied to every query on this entity.
 *
 * Inspired by EF Core HasQueryFilter(). Filters are applied automatically
 * by the Repository/QueryBuilder layer. They can be disabled per-query
 * via `$repo->withoutFilters()->findAll()`.
 *
 * Common use cases: multi-tenancy, soft delete, archival, status filtering.
 *
 * The `method` parameter references a static method on the entity class
 * that receives the QueryBuilder and applies the filter conditions.
 *
 * ```php
 * #[Entity(table: 'posts')]
 * #[SoftDeletes]
 * #[QueryFilter(method: 'applyTenantFilter')]
 * class Post {
 *     #[Field(type: 'integer')]
 *     public int $tenant_id;
 *
 *     public static function applyTenantFilter(QueryBuilder $qb): void {
 *         $qb->where('tenant_id', '=', TenantContext::current()->id);
 *     }
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class QueryFilter
{
    public function __construct(
        public readonly string $method,
    ) {}
}
