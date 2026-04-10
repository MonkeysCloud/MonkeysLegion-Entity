<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Metadata;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Per-index metadata extracted from #[Index] attributes.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class IndexMetadata
{
    /**
     * @param list<string> $columns
     */
    public function __construct(
        public readonly array $columns,
        public readonly ?string $name = null,
        public readonly bool $unique = false,
    ) {}
}
