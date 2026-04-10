<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Configure the join table for a Many-to-Many relationship.
 *
 * ```php
 * #[ManyToMany(targetEntity: Tag::class)]
 * #[JoinTable(name: 'post_tags', joinColumn: 'post_id', inverseColumn: 'tag_id')]
 * public array $tags = [];
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class JoinTable
{
    /**
     * @param string $name          Join table name.
     * @param string $joinColumn    FK column pointing to the owning entity.
     * @param string $inverseColumn FK column pointing to the inverse entity.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $joinColumn,
        public readonly string $inverseColumn,
    ) {}
}