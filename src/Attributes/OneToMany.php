<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * One-to-Many relationship definition.
 *
 * ```php
 * #[Entity(table: 'posts')]
 * class Post {
 *     #[OneToMany(targetEntity: Comment::class, mappedBy: 'post')]
 *     public array $comments = [];
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class OneToMany
{
    /**
     * @param class-string $targetEntity Target entity class.
     * @param string       $mappedBy     Property on the target that owns the relationship.
     * @param bool         $nullable     Whether the relation can be empty.
     */
    public function __construct(
        public readonly string $targetEntity,
        public readonly string $mappedBy,
        public readonly bool $nullable = true,
    ) {}
}