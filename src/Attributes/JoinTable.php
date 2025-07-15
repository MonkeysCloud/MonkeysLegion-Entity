<?php

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class JoinTable
{
    public function __construct(
        public string $name,
        public string $joinColumn,
        public string $inverseColumn,
    ) {}
}