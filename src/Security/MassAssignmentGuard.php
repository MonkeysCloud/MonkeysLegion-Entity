<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Security;

use MonkeysLegion\Entity\Exceptions\MassAssignmentException;
use MonkeysLegion\Entity\Metadata\MetadataRegistry;
use ReflectionClass;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Mass-assignment protection guard.
 *
 * Strategy:
 *  • If ANY property has #[Fillable], whitelist mode — only fillables allowed.
 *  • Otherwise, blacklist mode — all fields allowed EXCEPT #[Guarded].
 *  • #[Changeset] contexts override both modes for context-aware filling.
 *
 * ```php
 * MassAssignmentGuard::fill($user, ['name' => 'John', 'role' => 'admin']);
 * // Throws if 'role' is guarded
 *
 * MassAssignmentGuard::fill($user, $data, context: 'profile_update');
 * // Uses changeset rules for 'profile_update' context
 * ```
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
final class MassAssignmentGuard
{
    /**
     * Fill an entity with the given data, respecting mass-assignment rules.
     *
     * @param object               $entity  The entity to fill.
     * @param array<string, mixed> $data    Key-value pairs to assign.
     * @param string|null          $context Optional changeset context.
     * @param bool                 $silent  If true, silently skip disallowed fields instead of throwing.
     *
     * @throws MassAssignmentException When a disallowed field is present and $silent is false.
     */
    public static function fill(
        object $entity,
        array $data,
        ?string $context = null,
        bool $silent = false,
    ): void {
        $meta = MetadataRegistry::for($entity::class);

        // Determine allowed fields
        $allowed = self::resolveAllowed($meta, $context);

        $ref = new ReflectionClass($entity);

        foreach ($data as $field => $value) {
            if (!in_array($field, $allowed, true)) {
                if ($silent) {
                    continue;
                }
                throw new MassAssignmentException(sprintf(
                    'Mass assignment of field "%s" is not allowed on %s%s',
                    $field,
                    $entity::class,
                    $context !== null ? " (context: {$context})" : '',
                ));
            }

            if (!$ref->hasProperty($field)) {
                continue;
            }

            $prop = $ref->getProperty($field);

            // Use direct assignment for hooked properties (performance + correctness)
            $fieldMeta = $meta->fields[$field] ?? null;
            if ($fieldMeta !== null && $fieldMeta->hasHook) {
                $entity->{$field} = $value;
            } else {
                $prop->setValue($entity, $value);
            }
        }
    }

    /**
     * Resolve the list of allowed field names.
     *
     * @return list<string>
     */
    private static function resolveAllowed(
        \MonkeysLegion\Entity\Metadata\EntityMetadata $meta,
        ?string $context,
    ): array {
        // Changeset context takes priority
        if ($context !== null) {
            $contextFields = $meta->changesetFields($context);
            if ($contextFields !== null) {
                return $contextFields;
            }
        }

        // Whitelist mode (any #[Fillable] present)
        if ($meta->usesFillableWhitelist) {
            return $meta->fillable;
        }

        // Blacklist mode (all fields except #[Guarded] and virtual)
        $all = array_keys($meta->fields);

        return array_values(array_filter(
            $all,
            fn(string $name): bool => !in_array($name, $meta->guarded, true)
                && !in_array($name, $meta->virtual, true),
        ));
    }
}
