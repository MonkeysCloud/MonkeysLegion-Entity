<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Support;

use MonkeysLegion\Entity\Metadata\MetadataRegistry;
use ReflectionClass;
use SplObjectStorage;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Track original hydrated values per entity instance for dirty checking.
 *
 * Used by UnitOfWork for efficient UPDATE queries — only changed columns
 * are included in the SET clause.
 *
 * ```php
 * $tracker = new ChangeTracker();
 * $tracker->track($user); // snapshot original values
 * $user->name = 'New Name';
 * $tracker->isDirty($user);  // true
 * $tracker->getDirty($user); // ['name' => 'New Name']
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class ChangeTracker
{
    /** @var SplObjectStorage<object, array<string, mixed>> */
    private SplObjectStorage $originals;

    public function __construct()
    {
        $this->originals = new SplObjectStorage();
    }

    /**
     * Snapshot the current state of an entity for later comparison.
     */
    public function track(object $entity): void
    {
        $this->originals[$entity] = $this->snapshot($entity);
    }

    /**
     * Check whether an entity has any modified fields.
     */
    public function isDirty(object $entity): bool
    {
        return $this->getDirty($entity) !== [];
    }

    /**
     * Get the modified fields with their current values.
     *
     * @return array<string, mixed> Field name => current value.
     */
    public function getDirty(object $entity): array
    {
        if (!$this->originals->offsetExists($entity)) {
            return [];
        }

        $original = $this->originals[$entity];
        $current  = $this->snapshot($entity);
        $dirty    = [];

        foreach ($current as $field => $value) {
            if (!array_key_exists($field, $original) || $original[$field] !== $value) {
                $dirty[$field] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Get the original value of a field (or all originals).
     */
    public function getOriginal(object $entity, ?string $field = null): mixed
    {
        if (!$this->originals->offsetExists($entity)) {
            return $field !== null ? null : [];
        }

        $original = $this->originals[$entity];

        return $field !== null
            ? ($original[$field] ?? null)
            : $original;
    }

    /**
     * Remove tracking for an entity.
     */
    public function untrack(object $entity): void
    {
        if ($this->originals->offsetExists($entity)) {
            $this->originals->offsetUnset($entity);
        }
    }

    /**
     * Clear all tracked entities.
     */
    public function clear(): void
    {
        $this->originals = new SplObjectStorage();
    }

    // ── Internal ───────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function snapshot(object $entity): array
    {
        $meta = MetadataRegistry::for($entity::class);
        $ref  = new ReflectionClass($entity);
        $data = [];

        foreach ($meta->persistableFields() as $name) {
            if (!$ref->hasProperty($name)) {
                continue;
            }

            $prop = $ref->getProperty($name);
            if ($prop->isInitialized($entity)) {
                $data[$name] = $prop->getValue($entity);
            }
        }

        return $data;
    }
}
