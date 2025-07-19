<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * One-to-One relationship.
 * @usage #[OneToOne(targetEntity: Media::class, mappedBy: 'user', nullable: true)]
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class OneToOne
{
    public function __construct(
        public string  $targetEntity,
        public ?string $mappedBy    = null,
        public ?string $inversedBy  = null,
        public bool    $nullable    = true,
    ) {}
}