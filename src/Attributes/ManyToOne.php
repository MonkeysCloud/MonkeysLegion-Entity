<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Many-to-One relationship definition.
 *
 * ```php
 * #[Entity(table: 'comments')]
 * class Comment {
 *     #[ManyToOne(targetEntity: Post::class, inversedBy: 'comments')]
 *     public Post $post;
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ManyToOne
{
    /**
     * @param class-string $targetEntity Target entity class.
     * @param string|null  $inversedBy   Property on the target that maps the inverse side.
     * @param bool         $nullable     Whether the FK column allows NULL.
     */
    public function __construct(
        public readonly string $targetEntity,
        public readonly ?string $inversedBy = null,
        public readonly bool $nullable = true,
    ) {}
}