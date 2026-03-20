<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * Register an observer for an entity class.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class ObservedBy
{
    /**
     * @param string|array<string> $observer The class name(s) of the observer(s).
     */
    public function __construct(public string|array $observer) {}
}
