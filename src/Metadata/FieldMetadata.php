<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Metadata;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Per-field metadata extracted from #[Field] and property-level attributes.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class FieldMetadata
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?int $length = null,
        public readonly ?int $precision = null,
        public readonly ?int $scale = null,
        public readonly bool $nullable = false,
        public readonly mixed $default = null,
        public readonly bool $unique = false,
        public readonly bool $unsigned = false,
        public readonly bool $autoIncrement = false,
        public readonly bool $primaryKey = false,
        public readonly ?array $enumValues = null,
        public readonly ?string $comment = null,
        public readonly ?string $columnName = null,
        public readonly bool $isId = false,
        public readonly bool $isUuid = false,
        public readonly bool $isHidden = false,
        public readonly bool $isFillable = false,
        public readonly bool $isGuarded = false,
        public readonly bool $isVirtual = false,
        public readonly bool $isVersioned = false,
        public readonly ?string $castTo = null,
        public readonly bool $hasHook = false,
    ) {}
}
