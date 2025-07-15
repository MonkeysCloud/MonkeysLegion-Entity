<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * Many-to-Many relationship.
 * @usage #[ManyToMany(targetEntity: Tag::class, mappedBy: 'posts', joinTable: new JoinTable(...))]
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ManyToMany
{
    public function __construct(
        public string        $targetEntity,
        public ?string       $mappedBy    = null,
        public ?string       $inversedBy  = null,
        public ?JoinTable    $joinTable   = null,
    ) {}
}
