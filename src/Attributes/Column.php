<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * Annotate a property with column metadata.
 *
 * @param string|null $type     SQL type (e.g. "VARCHAR", "TEXT", "INT")
 * @param int|null    $length   Optional length (e.g. 255)
 * @param bool        $nullable Whether the column allows NULL
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Column
{
    public ?string $type;
    public ?int    $length;
    public bool    $nullable;

    public function __construct(
        ?string $type   = null,
        ?int    $length = null,
        bool    $nullable = false
    ) {
        $this->type     = $type;
        $this->length   = $length;
        $this->nullable = $nullable;
    }
}