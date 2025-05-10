<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * Indicates a UUID primary key; your generator can hook this.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Uuid {}