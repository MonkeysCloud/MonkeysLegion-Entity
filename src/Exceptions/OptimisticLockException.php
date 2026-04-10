<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Exceptions;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Thrown when a #[Versioned] entity's version does not match the
 * database row — another process modified the record.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
class OptimisticLockException extends \RuntimeException
{
    public function __construct(
        public readonly string $entityClass,
        public readonly mixed $entityId,
        public readonly int $expectedVersion,
        public readonly int $actualVersion,
    ) {
        parent::__construct(sprintf(
            'Optimistic lock failed for %s#%s: expected version %d, got %d',
            $entityClass,
            (string) $entityId,
            $expectedVersion,
            $actualVersion,
        ));
    }
}
