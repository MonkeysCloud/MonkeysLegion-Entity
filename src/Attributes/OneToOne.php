<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * One-to-One relationship definition.
 *
 * ```php
 * #[Entity(table: 'users')]
 * class User {
 *     #[OneToOne(targetEntity: Profile::class, mappedBy: 'user')]
 *     public ?Profile $profile = null;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class OneToOne
{
    /**
     * @param class-string $targetEntity Target entity class.
     * @param string|null  $mappedBy     Owning side property (if inverse side).
     * @param string|null  $inversedBy   Inverse side property (if owning side).
     * @param bool         $nullable     Whether the FK column allows NULL.
     */
    public function __construct(
        public readonly string $targetEntity,
        public readonly ?string $mappedBy = null,
        public readonly ?string $inversedBy = null,
        public readonly bool $nullable = true,
    ) {}
}