<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Attributes;

use Attribute;

/**
 * Marks a class property as a persisted field.
 *
 * Usage:
 *   #[Field(
 *       type: 'string',
 *       length: 255,
 *       nullable: false,
 *       default: null,
 *       unique: false,
 *       unsigned: false,
 *       autoIncrement: false,
 *       precision: null,
 *       scale: null,
 *       comment: null
 *   )]
 *
 * Supported `type` values and their SQL mappings:
 *
 * ────────────────┬───────────────────────────┬──────────────────────────────────────────────┐
 *  Attribute type │ PHP type                  │ SQL column type                             │
 * ────────────────┼───────────────────────────┼──────────────────────────────────────────────┤
 *  string         │ string                    │ VARCHAR(length)          (default length=255)│
 *  char           │ string                    │ CHAR(length)             (default length=255)│
 *  text           │ string                    │ TEXT                                        │
 *  mediumText     │ string                    │ MEDIUMTEXT                                  │
 *  longText       │ string                    │ LONGTEXT                                    │
 *  integer / int  │ int                       │ INT                                         │
 *  tinyInt        │ int                       │ TINYINT(1)                                  │
 *  smallInt       │ int                       │ SMALLINT                                    │
 *  bigInt         │ int                       │ BIGINT                                      │
 *  unsignedBigInt │ int                       │ BIGINT UNSIGNED                             │
 *  decimal        │ string                    │ DECIMAL(precision,scale)                    │
 *  float          │ float                     │ DOUBLE                                      │
 *  boolean / bool │ bool                      │ TINYINT(1)                                  │
 *  date           │ \DateTimeImmutable        │ DATE                                        │
 *  time           │ \DateTimeImmutable        │ TIME                                        │
 *  datetime       │ \DateTimeImmutable        │ DATETIME                                    │
 *  datetimetz     │ \DateTimeImmutable        │ DATETIME (with timezone)                    │
 *  timestamp      │ \DateTimeImmutable        │ TIMESTAMP                                   │
 *  timestamptz    │ \DateTimeImmutable        │ TIMESTAMP (with timezone)                   │
 *  year           │ int                       │ YEAR                                        │
 *  uuid           │ string                    │ CHAR(36)                                    │
 *  binary / blob  │ string                    │ BLOB                                        │
 *  json           │ array|object              │ JSON                                        │
 *  simple_json    │ array|object              │ JSON                                        │
 *  array          │ array                     │ LONGTEXT (serialized)                       │
 *  simple_array   │ array                     │ VARCHAR (comma-separated)                   │
 *  enum           │ string                    │ ENUM('a','b',…)                              │
 *  set            │ string                    │ SET('a','b',…)                               │
 *  geometry       │ string                    │ GEOMETRY                                    │
 *  point          │ string                    │ POINT                                       │
 *  linestring     │ string                    │ LINESTRING                                  │
 *  polygon        │ string                    │ POLYGON                                     │
 *  ipAddress      │ string                    │ VARCHAR(45)                                 │
 *  macAddress     │ string                    │ VARCHAR(17)                                 │
 *
 * Extended options:
 *   • length         (int|null)    — for types that accept a length (string, char, uuid)
 *   • precision      (int|null)    — for DECIMAL precision
 *   • scale          (int|null)    — for DECIMAL scale
 *   • nullable       (bool)        — allow NULL?
 *   • default        (mixed)       — default value (literal-injected)
 *   • unique         (bool)        — add UNIQUE constraint
 *   • unsigned       (bool)        — for integer types
 *   • autoIncrement  (bool)        — for primary IDs
 *   • comment        (string|null) — add a column comment
 *
 * Example:
 *   #[Field(
 *       type: 'decimal',
 *       precision: 10,
 *       scale: 2,
 *       default: 0.00,
 *       nullable: false,
 *       comment: 'Order total'
 *   )]
 *   private string $total;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field
{
    public function __construct(
        public string      $type,
        public ?int        $length        = null,
        public ?int        $precision     = null,
        public ?int        $scale         = null,
        public bool        $nullable      = false,
        public mixed       $default       = null,
        public bool        $unique        = false,
        public bool        $unsigned      = false,
        public bool        $autoIncrement = false,
        public ?string     $comment       = null,
    ) {}
}