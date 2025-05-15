<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * Many‑to‑One relationship.
 * @usage #[ManyToOne(targetEntity: Post::class, inversedBy: 'comments')]
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ManyToOne
{
    public function __construct(
        public string $targetEntity,
        public ?string $inversedBy = null,
    ) {}
}