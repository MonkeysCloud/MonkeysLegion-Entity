<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Many-to-Many relationship definition.
 *
 * ```php
 * #[Entity(table: 'posts')]
 * class Post {
 *     #[ManyToMany(targetEntity: Tag::class, inversedBy: 'posts')]
 *     #[JoinTable(name: 'post_tags', joinColumn: 'post_id', inverseColumn: 'tag_id')]
 *     public array $tags = [];
 * }
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ManyToMany
{
    /**
     * @param class-string   $targetEntity Target entity class.
     * @param string|null    $mappedBy     Owning side property (if inverse side).
     * @param string|null    $inversedBy   Inverse side property (if owning side).
     * @param JoinTable|null $joinTable    Join table configuration.
     * @param bool           $nullable     Whether the relation can be empty.
     */
    public function __construct(
        public readonly string $targetEntity,
        public readonly ?string $mappedBy = null,
        public readonly ?string $inversedBy = null,
        public readonly ?JoinTable $joinTable = null,
        public readonly bool $nullable = true,
    ) {}
}
