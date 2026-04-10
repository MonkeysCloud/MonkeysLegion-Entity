<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Metadata;

use MonkeysLegion\Entity\Attributes\AuditTrail;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Cached metadata object per entity class.
 *
 * Built once per class by MetadataRegistry, reused everywhere.
 * Eliminates repeated reflection calls during hydration, extraction,
 * and query building — zero reflection after first resolution.
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class EntityMetadata
{
    /**
     * @param class-string                    $className
     * @param array<string, FieldMetadata>    $fields        Keyed by property name.
     * @param list<string>                    $fillable      Property names with #[Fillable].
     * @param list<string>                    $guarded       Property names with #[Guarded].
     * @param list<string>                    $hidden        Property names with #[Hidden].
     * @param list<string>                    $virtual       Property names with #[Virtual].
     * @param list<IndexMetadata>             $indexes       All index definitions.
     * @param array<string, string>           $casts         Property => cast target.
     * @param list<string>                    $observers     Observer class names from #[ObservedBy].
     * @param list<string>                    $queryFilters  Static method names from #[QueryFilter].
     * @param array<string, list<string>>     $changesets    Context => list of field names.
     */
    public function __construct(
        public readonly string $className,
        public readonly string $table,
        public readonly ?string $primaryKey = null,
        public readonly bool $softDeletes = false,
        public readonly ?string $softDeleteColumn = null,
        public readonly bool $timestamps = false,
        public readonly string $createdColumn = 'created_at',
        public readonly string $updatedColumn = 'updated_at',
        public readonly bool $immutable = false,
        public readonly ?string $versionField = null,
        public readonly ?AuditTrail $auditTrail = null,
        public readonly array $fields = [],
        public readonly array $fillable = [],
        public readonly array $guarded = [],
        public readonly array $hidden = [],
        public readonly array $virtual = [],
        public readonly array $indexes = [],
        public readonly array $casts = [],
        public readonly array $observers = [],
        public readonly array $queryFilters = [],
        public readonly array $changesets = [],
    ) {
        // Pre-compute persistable fields once; reused by Hydrator, ChangeTracker, etc.
        $this->persistableFieldsCache = array_values(array_filter(
            array_keys($this->fields),
            fn(string $name): bool => !in_array($name, $this->virtual, true),
        ));

        // Pre-compute column→property map for #[Column(name: ...)] support.
        // Only includes fields where columnName differs from property name.
        $map = [];
        foreach ($this->fields as $name => $field) {
            if ($field->columnName !== null && $field->columnName !== $name) {
                $map[$field->columnName] = $name;
            }
        }
        $this->columnPropertyMap = $map;
    }

    /** @var list<string> Pre-computed list of persistable field names (excludes virtual). */
    private readonly array $persistableFieldsCache;

    /** @var array<string, string> DB column name → PHP property name (only for aliased columns). */
    private readonly array $columnPropertyMap;

    // ── Convenience Accessors ──────────────────────────────────

    /**
     * Check if the entity uses whitelist mode (any #[Fillable] present).
     */
    public bool $usesFillableWhitelist {
        get => $this->fillable !== [];
    }

    /**
     * Check if the entity has optimistic locking enabled.
     */
    public bool $isVersioned {
        get => $this->versionField !== null;
    }

    /**
     * Check if the entity has audit trail shadow columns.
     */
    public bool $hasAuditTrail {
        get => $this->auditTrail !== null;
    }

    /**
     * Get field names suitable for database persistence (excludes virtual).
     *
     * @return list<string>
     */
    public function persistableFields(): array
    {
        return $this->persistableFieldsCache;
    }

    /**
     * Get the DB column→property map for aliased columns.
     *
     * @return array<string, string> DB column name → PHP property name.
     */
    public function columnToPropertyMap(): array
    {
        return $this->columnPropertyMap;
    }

    /**
     * Get the list of allowed fields for a changeset context.
     *
     * @return list<string>|null Null if the context is not defined.
     */
    public function changesetFields(string $context): ?array
    {
        return $this->changesets[$context] ?? null;
    }
}
