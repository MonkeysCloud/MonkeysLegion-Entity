<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Support;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Value object passed to subscribers and observers during lifecycle events.
 *
 * Contains the entity instance, event name, and any changed fields
 * (for afterUpdate events).
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final readonly class EntityEvent
{
    /**
     * @param array<string, mixed> $changes Changed fields (name => new value).
     */
    public function __construct(
        public string $event,
        public object $entity,
        public array $changes = [],
    ) {}
}
