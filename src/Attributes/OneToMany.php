<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * One‑to‑Many relationship.
 * @usage #[OneToMany(targetEntity: Comment::class, mappedBy: 'post', nullable: true)]
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class OneToMany
{
    public function __construct(
        public string $targetEntity,
        public string $mappedBy,
        public bool   $nullable    = true,
    ) {}
}