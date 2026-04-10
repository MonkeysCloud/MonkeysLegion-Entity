<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity;

use DateTimeImmutable;
use DateTimeZone;
use MonkeysLegion\Entity\Contracts\CastInterface;
use MonkeysLegion\Entity\Metadata\EntityMetadata;
use MonkeysLegion\Entity\Metadata\FieldMetadata;
use MonkeysLegion\Entity\Metadata\MetadataRegistry;
use MonkeysLegion\Entity\Observers\LifecycleDispatcher;
use MonkeysLegion\Entity\Utils\Uuid;
use ReflectionClass;
use ReflectionProperty;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * High-performance entity hydrator and extractor.
 *
 * PHP 8.4 features used:
 *  • Property hook awareness — uses direct assignment for hooked props
 *  • Backed enum auto-casting via ::from()
 *  • Static metadata cache via MetadataRegistry (zero reflection after boot)
 *
 * v2 improvements over v1:
 *  • Cast pipeline (#[Cast] processed before raw type coercion)
 *  • #[Hidden]-aware extraction
 *  • #[Virtual] fields skipped during extract
 *  • #[Timestamps] auto-injection
 *  • toArray() / toJson() convenience methods
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class Hydrator
{
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $reflectionCache = [];

    /** @var array<string, CastInterface> */
    private static array $castInstances = [];

    // ── Hydration ──────────────────────────────────────────────

    /**
     * Hydrate an entity from a database row.
     *
     * @param class-string         $class
     * @param array<string, mixed> $row
     *
     * @throws \ReflectionException
     */
    public static function hydrate(string $class, array|object $row): object
    {
        $ref  = self::reflect($class);
        $meta = MetadataRegistry::for($class);
        $obj  = $ref->newInstanceWithoutConstructor();

        if (is_object($row)) {
            $row = (array) $row;
        }

        foreach ($row as $col => $val) {
            if (!$ref->hasProperty($col)) {
                continue;
            }

            $fieldMeta = $meta->fields[$col] ?? null;
            $prop      = $ref->getProperty($col);
            $value     = self::castValue($val, $prop, $fieldMeta, $obj);

            self::assignProperty($prop, $obj, $value, $fieldMeta);
        }

        // Auto-generate UUID v4 for any #[Uuid] fields not present in the row
        foreach ($meta->fields as $name => $fieldMeta) {
            if (!$fieldMeta->isUuid) {
                continue;
            }
            if (!$ref->hasProperty($name)) {
                continue;
            }
            $prop = $ref->getProperty($name);
            if (!$prop->isInitialized($obj)) {
                $prop->setValue($obj, Uuid::v4());
            }
        }

        LifecycleDispatcher::dispatch('hydrated', $obj);

        return $obj;
    }

    // ── Extraction ─────────────────────────────────────────────

    /**
     * Extract data from an entity for persistence.
     *
     * @param object       $entity
     * @param list<string> $fields       Optional subset of fields.
     * @param bool         $includeHidden Whether to include #[Hidden] fields.
     * @param bool         $forInsert    If true, auto-set created_at timestamp.
     *
     * @return array<string, mixed>
     */
    public static function extract(
        object $entity,
        array $fields = [],
        bool $includeHidden = true,
        bool $forInsert = false,
    ): array {
        $meta = MetadataRegistry::for($entity::class);
        $ref  = self::reflect($entity::class);
        $data = [];
        $now  = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $targetFields = $fields !== []
            ? $fields
            : $meta->persistableFields();

        foreach ($targetFields as $name) {
            // Skip hidden in serialization mode
            if (!$includeHidden && in_array($name, $meta->hidden, true)) {
                continue;
            }

            if (!$ref->hasProperty($name)) {
                continue;
            }

            $prop = $ref->getProperty($name);
            if (!$prop->isInitialized($entity)) {
                continue;
            }

            $value     = $prop->getValue($entity);
            $fieldMeta = $meta->fields[$name] ?? null;
            $data[$name] = self::decastValue($value, $fieldMeta, $entity);
        }

        // Auto-inject timestamps
        if ($meta->timestamps) {
            if ($forInsert) {
                $data[$meta->createdColumn] ??= $now->format('Y-m-d H:i:s');
            }
            $data[$meta->updatedColumn] = $now->format('Y-m-d H:i:s');
        }

        return $data;
    }

    // ── Serialization ──────────────────────────────────────────

    /**
     * Convert an entity to an array, respecting #[Hidden] and including #[Virtual].
     *
     * @return array<string, mixed>
     */
    public static function toArray(object $entity): array
    {
        $meta = MetadataRegistry::for($entity::class);
        $ref  = self::reflect($entity::class);
        $data = [];

        foreach ($meta->fields as $name => $fieldMeta) {
            if ($fieldMeta->isHidden) {
                continue;
            }

            if (!$ref->hasProperty($name)) {
                continue;
            }

            $prop = $ref->getProperty($name);
            if (!$prop->isInitialized($entity)) {
                continue;
            }

            $value = $prop->getValue($entity);
            $data[$name] = self::serializeValue($value);
        }

        return $data;
    }

    /**
     * Convert an entity to a JSON string.
     */
    public static function toJson(object $entity, int $flags = 0): string
    {
        return json_encode(
            self::toArray($entity),
            $flags | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    // ── Cast Pipeline ──────────────────────────────────────────

    /**
     * Cast a raw DB value to the appropriate PHP type.
     */
    private static function castValue(
        mixed $val,
        ReflectionProperty $prop,
        ?FieldMetadata $fieldMeta,
        object $entity,
    ): mixed {
        if ($val === null) {
            return null;
        }

        // 1. Custom #[Cast] takes priority
        if ($fieldMeta?->castTo !== null) {
            return self::applyCast($val, $fieldMeta->castTo, $prop, $entity);
        }

        // 2. Backed enum detection via reflection type
        $type = $prop->getType();
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();
            if (is_subclass_of($typeName, \BackedEnum::class)) {
                return $typeName::from($val);
            }
        }

        // 3. Raw type coercion
        return self::coerceType($val, $fieldMeta);
    }

    /**
     * Apply a #[Cast] transformation during hydration.
     */
    private static function applyCast(
        mixed $val,
        string $castTo,
        ReflectionProperty $prop,
        object $entity,
    ): mixed {
        // Backed enum
        if (is_subclass_of($castTo, \BackedEnum::class)) {
            return $castTo::from($val);
        }

        // CastInterface implementation
        if (is_subclass_of($castTo, CastInterface::class)) {
            $caster = self::$castInstances[$castTo] ??= new $castTo();
            return $caster->get($val, $prop->getName(), $entity);
        }

        // Scalar type cast
        return match ($castTo) {
            'int', 'integer'         => (int) $val,
            'float', 'double'        => (float) $val,
            'bool', 'boolean'        => (bool) $val,
            'string'                 => (string) $val,
            'array'                  => is_string($val) ? json_decode($val, true) ?? [] : (array) $val,
            'datetime', DateTimeImmutable::class => new DateTimeImmutable((string) $val, new DateTimeZone('UTC')),
            default                  => $val,
        };
    }

    /**
     * Raw type coercion based on #[Field] type.
     */
    private static function coerceType(mixed $val, ?FieldMetadata $fieldMeta): mixed
    {
        if ($fieldMeta === null) {
            return $val;
        }

        $lc = strtolower($fieldMeta->type);

        return match (true) {
            // Date/time types
            in_array($lc, ['datetime', 'datetimeimmutable', 'timestamp', 'timestamptz'], true)
                => new DateTimeImmutable((string) $val, new DateTimeZone('UTC')),
            $lc === 'date'
                => new DateTimeImmutable($val . ' 00:00:00', new DateTimeZone('UTC')),
            $lc === 'time'
                => new DateTimeImmutable(date('Y-m-d') . ' ' . $val, new DateTimeZone('UTC')),
            // Integer types
            in_array($lc, ['int', 'integer', 'bigint', 'smallint', 'tinyint', 'unsignedbigint'], true)
                => (int) $val,
            // Float types
            in_array($lc, ['float', 'double', 'decimal'], true)
                => is_numeric($val) ? (float) $val : $val,
            // Boolean
            in_array($lc, ['bool', 'boolean'], true)
                => (bool) $val,
            // JSON
            in_array($lc, ['json', 'simple_json'], true)
                => is_string($val) ? (json_decode($val, true) ?? null) : $val,
            // Array
            in_array($lc, ['array', 'simple_array'], true)
                => self::coerceArray($val),
            // String
            in_array($lc, ['string', 'text', 'char', 'mediumtext', 'longtext'], true)
                => (string) $val,
            default => $val,
        };
    }

    /**
     * Coerce a value to an array from JSON or comma-separated string.
     */
    private static function coerceArray(mixed $val): array
    {
        if (!is_string($val)) {
            return (array) $val;
        }

        $trimmed = trim($val);
        if ($trimmed !== '' && ($trimmed[0] === '[' || $trimmed[0] === '{')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $val === '' ? [] : explode(',', $val);
    }

    // ── De-cast (for extraction) ───────────────────────────────

    /**
     * Convert a PHP value back to a database-friendly format.
     */
    private static function decastValue(mixed $value, ?FieldMetadata $fieldMeta, object $entity): mixed
    {
        if ($value === null) {
            return null;
        }

        // Custom CastInterface::set() takes priority
        if ($fieldMeta?->castTo !== null && is_subclass_of($fieldMeta->castTo, CastInterface::class)) {
            $caster = self::$castInstances[$fieldMeta->castTo] ??= new ($fieldMeta->castTo)();
            return $caster->set($value, $fieldMeta->name, $entity);
        }

        return match (true) {
            $value instanceof \BackedEnum             => $value->value,
            $value instanceof DateTimeImmutable,
            $value instanceof \DateTime               => $value->format('Y-m-d H:i:s'),
            is_bool($value)                           => $value ? 1 : 0,
            is_array($value)                          => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default                                   => $value,
        };
    }

    /**
     * Serialize a value for toArray() output.
     */
    private static function serializeValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \BackedEnum     => $value->value,
            $value instanceof DateTimeImmutable,
            $value instanceof \DateTime       => $value->format('c'),
            default                           => $value,
        };
    }

    // ── Property Assignment ────────────────────────────────────

    /**
     * Assign a value to a property, using direct assignment for hooked properties.
     */
    private static function assignProperty(
        ReflectionProperty $prop,
        object $entity,
        mixed $value,
        ?FieldMetadata $fieldMeta,
    ): void {
        // Null on non-nullable → skip (leave uninitialized)
        if ($value === null && !self::isNullable($prop)) {
            return;
        }

        // Hooked properties → direct assignment (invokes set hook)
        if ($fieldMeta?->hasHook === true) {
            $entity->{$prop->getName()} = $value;
            return;
        }

        $prop->setValue($entity, $value);
    }

    /**
     * Check if a property allows null.
     */
    private static function isNullable(ReflectionProperty $prop): bool
    {
        $type = $prop->getType();

        if ($type === null) {
            return true;
        }

        return $type->allowsNull();
    }

    // ── Reflection Cache ───────────────────────────────────────

    /**
     * @param class-string $class
     *
     * @return ReflectionClass<object>
     */
    private static function reflect(string $class): ReflectionClass
    {
        return self::$reflectionCache[$class] ??= new ReflectionClass($class);
    }

    /**
     * Clear all caches (primarily for testing).
     */
    public static function clearCache(): void
    {
        self::$reflectionCache = [];
        self::$castInstances   = [];
    }
}
