<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Register one or more observers for an entity class.
 *
 * Observers are per-entity lifecycle hooks. For cross-cutting concerns
 * across multiple entity types, use #[Subscribe] instead.
 *
 * ```php
 * #[Entity(table: 'users')]
 * #[ObservedBy(UserObserver::class)]
 * class User { ... }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class ObservedBy
{
    /**
     * @param class-string|list<class-string> $observer Observer class name(s).
     */
    public function __construct(
        public readonly string|array $observer,
    ) {}
}
