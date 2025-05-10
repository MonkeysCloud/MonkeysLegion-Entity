<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/** Mark the primary key property. */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Id {}