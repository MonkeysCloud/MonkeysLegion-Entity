<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * Mark a class as an Entity to be scanned by EntityScanner.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Entity
{
    public function __construct(public ?string $table = null) {}
}