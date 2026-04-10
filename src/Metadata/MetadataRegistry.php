<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Metadata;

use MonkeysLegion\Entity\Attributes\AuditTrail;
use MonkeysLegion\Entity\Attributes\Cast;
use MonkeysLegion\Entity\Attributes\Changeset;
use MonkeysLegion\Entity\Attributes\Column;
use MonkeysLegion\Entity\Attributes\Entity;
use MonkeysLegion\Entity\Attributes\Field;
use MonkeysLegion\Entity\Attributes\Fillable;
use MonkeysLegion\Entity\Attributes\Guarded;
use MonkeysLegion\Entity\Attributes\Hidden;
use MonkeysLegion\Entity\Attributes\Id;
use MonkeysLegion\Entity\Attributes\Immutable;
use MonkeysLegion\Entity\Attributes\Index;
use MonkeysLegion\Entity\Attributes\ObservedBy;
use MonkeysLegion\Entity\Attributes\QueryFilter;
use MonkeysLegion\Entity\Attributes\SoftDeletes;
use MonkeysLegion\Entity\Attributes\Timestamps;
use MonkeysLegion\Entity\Attributes\Uuid;
use MonkeysLegion\Entity\Attributes\Versioned;
use MonkeysLegion\Entity\Attributes\Virtual;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Static registry that parses entity classes via reflection ONCE, then
 * caches the resulting EntityMetadata for all subsequent calls.
 *
 * Performance target: zero reflection calls after first resolution.
 * Mirrors the static $reflectionCache pattern used in the DI container.
 *
 * ```php
 * $meta = MetadataRegistry::for(Order::class);
 * $meta->table;       // 'orders'
 * $meta->fillable;    // ['status', 'notes']
 * $meta->isVersioned; // true
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class MetadataRegistry
{
    /** @var array<class-string, EntityMetadata> */
    private static array $cache = [];

    /**
     * Resolve (or retrieve from cache) the metadata for an entity class.
     *
     * @param class-string $class
     */
    public static function for(string $class): EntityMetadata
    {
        return self::$cache[$class] ??= self::parse($class);
    }

    /**
     * Clear the metadata cache (primarily for testing).
     */
    public static function clear(): void
    {
        self::$cache = [];
    }

    /**
     * Check whether metadata has been cached for a class.
     *
     * @param class-string $class
     */
    public static function has(string $class): bool
    {
        return isset(self::$cache[$class]);
    }

    // ── Internal Parser ────────────────────────────────────────

    /**
     * @param class-string $class
     */
    private static function parse(string $class): EntityMetadata
    {
        $ref = new ReflectionClass($class);

        $classAttrs   = self::parseClassAttributes($ref);
        $classIndexes = self::parseClassIndexes($ref);
        $changesets   = self::parseChangesets($ref);
        $propData     = self::parseProperties($ref, $classIndexes);

        return new EntityMetadata(
            className: $class,
            table: $classAttrs['table'],
            primaryKey: $propData['primaryKey'],
            softDeletes: $classAttrs['softDeletes'] !== null,
            softDeleteColumn: $classAttrs['softDeletes']?->column,
            timestamps: $classAttrs['timestamps'] !== null,
            createdColumn: $classAttrs['timestamps']?->createdColumn ?? 'created_at',
            updatedColumn: $classAttrs['timestamps']?->updatedColumn ?? 'updated_at',
            immutable: $classAttrs['immutable'],
            versionField: $propData['versionField'],
            auditTrail: $classAttrs['auditTrail'],
            fields: $propData['fields'],
            fillable: $propData['fillable'],
            guarded: $propData['guarded'],
            hidden: $propData['hidden'],
            virtual: $propData['virtual'],
            indexes: $propData['indexes'],
            casts: $propData['casts'],
            observers: $classAttrs['observers'],
            queryFilters: $classAttrs['queryFilters'],
            changesets: $changesets,
        );
    }

    // ── Class-Level Parsing ────────────────────────────────────

    /**
     * Parse all class-level attributes into a keyed array.
     *
     * @param ReflectionClass<object> $ref
     *
     * @return array{
     *     table: string,
     *     softDeletes: ?SoftDeletes,
     *     timestamps: ?Timestamps,
     *     immutable: bool,
     *     auditTrail: ?AuditTrail,
     *     observers: list<string>,
     *     queryFilters: list<string>,
     * }
     */
    private static function parseClassAttributes(ReflectionClass $ref): array
    {
        $entityAttr = self::getAttribute($ref, Entity::class);

        // Observers
        $observers = [];
        foreach ($ref->getAttributes(ObservedBy::class) as $attr) {
            $obs = $attr->newInstance()->observer;
            foreach ((array) $obs as $o) {
                $observers[] = (string) $o;
            }
        }

        // Query Filters
        $queryFilters = [];
        foreach ($ref->getAttributes(QueryFilter::class) as $attr) {
            $queryFilters[] = $attr->newInstance()->method;
        }

        return [
            'table'        => $entityAttr?->table ?? self::classToTable($ref->getShortName()),
            'softDeletes'  => self::getAttribute($ref, SoftDeletes::class),
            'timestamps'   => self::getAttribute($ref, Timestamps::class),
            'immutable'    => self::hasAttribute($ref, Immutable::class),
            'auditTrail'   => self::getAttribute($ref, AuditTrail::class),
            'observers'    => $observers,
            'queryFilters' => $queryFilters,
        ];
    }

    /**
     * Parse class-level #[Index] attributes.
     *
     * @param ReflectionClass<object> $ref
     *
     * @return list<IndexMetadata>
     */
    private static function parseClassIndexes(ReflectionClass $ref): array
    {
        $indexes = [];
        foreach ($ref->getAttributes(Index::class) as $attr) {
            $idx = $attr->newInstance();
            $indexes[] = new IndexMetadata(
                columns: $idx->columns,
                name: $idx->name,
                unique: $idx->unique,
            );
        }
        return $indexes;
    }

    /**
     * Parse #[Changeset] attributes from static methods.
     *
     * @param ReflectionClass<object> $ref
     *
     * @return array<string, list<string>>
     */
    private static function parseChangesets(ReflectionClass $ref): array
    {
        $changesets = [];
        foreach ($ref->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(Changeset::class) as $attr) {
                $cs = $attr->newInstance();
                $changesets[$cs->context] = $method->invoke(null);
            }
        }
        return $changesets;
    }

    // ── Property-Level Parsing ─────────────────────────────────

    /**
     * Parse all property-level attributes and build field metadata.
     *
     * @param ReflectionClass<object> $ref
     * @param list<IndexMetadata>     $indexes Mutable — property indexes appended.
     *
     * @return array{
     *     fields: array<string, FieldMetadata>,
     *     fillable: list<string>,
     *     guarded: list<string>,
     *     hidden: list<string>,
     *     virtual: list<string>,
     *     casts: array<string, string>,
     *     indexes: list<IndexMetadata>,
     *     primaryKey: ?string,
     *     versionField: ?string,
     * }
     */
    private static function parseProperties(ReflectionClass $ref, array $indexes): array
    {
        $fields       = [];
        $fillable     = [];
        $guarded      = [];
        $hidden       = [];
        $virtual      = [];
        $casts        = [];
        $primaryKey   = null;
        $versionField = null;

        foreach ($ref->getProperties() as $prop) {
            $fieldAttr = self::getAttribute($prop, Field::class);
            if ($fieldAttr === null && !self::hasAttribute($prop, Virtual::class)) {
                continue;
            }

            $parsed = self::parseSingleProperty($prop, $fieldAttr);

            $name = $parsed['meta']->name;
            $fields[$name] = $parsed['meta'];

            if ($parsed['meta']->isId)       { $primaryKey = $name; }
            if ($parsed['meta']->isVersioned) { $versionField = $name; }
            if ($parsed['meta']->isHidden)    { $hidden[] = $name; }
            if ($parsed['meta']->isFillable)  { $fillable[] = $name; }
            if ($parsed['meta']->isGuarded)   { $guarded[] = $name; }
            if ($parsed['meta']->isVirtual)   { $virtual[] = $name; }
            if ($parsed['meta']->castTo !== null) { $casts[$name] = $parsed['meta']->castTo; }

            // Merge property-level indexes
            foreach ($parsed['indexes'] as $idx) {
                $indexes[] = $idx;
            }
        }

        return [
            'fields'       => $fields,
            'fillable'     => $fillable,
            'guarded'      => $guarded,
            'hidden'       => $hidden,
            'virtual'      => $virtual,
            'casts'        => $casts,
            'indexes'      => $indexes,
            'primaryKey'   => $primaryKey,
            'versionField' => $versionField,
        ];
    }

    /**
     * Parse a single property's attributes into FieldMetadata + indexes.
     *
     * @return array{meta: FieldMetadata, indexes: list<IndexMetadata>}
     */
    private static function parseSingleProperty(
        ReflectionProperty $prop,
        ?Field $fieldAttr,
    ): array {
        $name = $prop->getName();

        // Detect PHP 8.4 property hooks
        $hasHook = method_exists($prop, 'hasHook')
            ? ($prop->hasHook(\PropertyHookType::Set) || $prop->hasHook(\PropertyHookType::Get))
            : false;

        $castAttr   = self::getAttribute($prop, Cast::class);
        $columnAttr = self::getAttribute($prop, Column::class);
        $isId       = self::hasAttribute($prop, Id::class);

        // Property-level indexes
        $indexes = [];
        foreach ($prop->getAttributes(Index::class) as $idxAttr) {
            $idx = $idxAttr->newInstance();
            $indexes[] = new IndexMetadata(
                columns: $idx->columns !== [] ? $idx->columns : [$name],
                name: $idx->name,
                unique: $idx->unique,
            );
        }

        $meta = new FieldMetadata(
            name: $name,
            type: $fieldAttr?->type ?? 'string',
            length: $fieldAttr?->length,
            precision: $fieldAttr?->precision,
            scale: $fieldAttr?->scale,
            nullable: $fieldAttr?->nullable ?? false,
            default: $fieldAttr?->default,
            unique: $fieldAttr?->unique ?? false,
            unsigned: $fieldAttr?->unsigned ?? false,
            autoIncrement: $fieldAttr?->autoIncrement ?? false,
            primaryKey: $isId || ($fieldAttr?->primaryKey ?? false),
            enumValues: $fieldAttr?->enumValues,
            comment: $fieldAttr?->comment,
            columnName: $columnAttr?->name,
            isId: $isId,
            isUuid: self::hasAttribute($prop, Uuid::class),
            isHidden: self::hasAttribute($prop, Hidden::class),
            isFillable: self::hasAttribute($prop, Fillable::class),
            isGuarded: self::hasAttribute($prop, Guarded::class),
            isVirtual: self::hasAttribute($prop, Virtual::class),
            isVersioned: self::hasAttribute($prop, Versioned::class),
            castTo: $castAttr?->castTo,
            hasHook: $hasHook,
        );

        return ['meta' => $meta, 'indexes' => $indexes];
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * @template T of object
     *
     * @param ReflectionClass<object>|ReflectionProperty|ReflectionMethod $target
     * @param class-string<T>                                             $attribute
     *
     * @return T|null
     */
    private static function getAttribute(
        ReflectionClass|ReflectionProperty|ReflectionMethod $target,
        string $attribute,
    ): ?object {
        $attrs = $target->getAttributes($attribute);
        return $attrs !== [] ? $attrs[0]->newInstance() : null;
    }

    /**
     * @param ReflectionClass<object>|ReflectionProperty $target
     * @param class-string                                $attribute
     */
    private static function hasAttribute(
        ReflectionClass|ReflectionProperty $target,
        string $attribute,
    ): bool {
        return $target->getAttributes($attribute) !== [];
    }

    /**
     * Convert PascalCase class name to snake_case plural table name.
     * E.g. OrderItem → order_items.
     */
    private static function classToTable(string $shortName): string
    {
        $snake = strtolower((string) preg_replace('/[A-Z]/', '_$0', lcfirst($shortName)));
        return $snake . 's';
    }
}
