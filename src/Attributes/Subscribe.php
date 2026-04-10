<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Mark a class as a global entity subscriber for cross-cutting concerns.
 *
 * Inspired by TypeORM @EventSubscriber() and Django signals.
 * While #[ObservedBy] registers per-entity observers, #[Subscribe]
 * creates subscribers that can handle lifecycle events for multiple
 * entity types — or ALL entities when $entities is empty.
 *
 * Subscribers are auto-discovered via attribute scanning and resolved
 * through the DI container when available.
 *
 * ```php
 * #[Subscribe(entities: [Order::class, Invoice::class])]
 * class AuditSubscriber {
 *     public function afterInsert(object $entity, EntityEvent $event): void {
 *         AuditLog::record('created', $entity, $event->changes);
 *     }
 *     public function afterUpdate(object $entity, EntityEvent $event): void {
 *         AuditLog::record('updated', $entity, $event->changes);
 *     }
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Subscribe
{
    /**
     * @param list<class-string> $entities Entity classes to listen to (empty = all).
     */
    public function __construct(
        public readonly array $entities = [],
    ) {}
}
