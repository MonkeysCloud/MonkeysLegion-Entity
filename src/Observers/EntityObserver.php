<?php
declare(strict_types=1);

namespace MonkeysLegion\Entity\Observers;

/**
 * MonkeysLegion Framework — Entity Package
 *
 * Base class for entity observers.
 *
 * All methods are optional — override only the lifecycle events you
 * need. The LifecycleDispatcher calls these methods automatically
 * when registered via #[ObservedBy].
 *
 * Available events:
 *  • creating / created   — before/after INSERT
 *  • updating / updated   — before/after UPDATE
 *  • saving / saved       — before/after INSERT or UPDATE
 *  • deleting / deleted   — before/after DELETE
 *  • restoring / restored — before/after soft-delete restore
 *  • replicating          — before entity cloning
 *  • hydrated             — after hydration from database
 *
 * @template T of object
 *
 * @copyright 2026 MonkeysCloud Team
 * @license   MIT
 */
abstract class EntityObserver
{
    /**
     * Triggered before an entity is created in the database.
     *
     * @param T $entity
     */
    public function creating(object $entity): void {}

    /**
     * Triggered after an entity is created in the database.
     *
     * @param T $entity
     */
    public function created(object $entity): void {}

    /**
     * Triggered before an entity is updated in the database.
     *
     * @param T $entity
     */
    public function updating(object $entity): void {}

    /**
     * Triggered after an entity is updated in the database.
     *
     * @param T $entity
     */
    public function updated(object $entity): void {}

    /**
     * Triggered before an entity is saved (called for both create and update).
     *
     * @param T $entity
     */
    public function saving(object $entity): void {}

    /**
     * Triggered after an entity is saved (called for both create and update).
     *
     * @param T $entity
     */
    public function saved(object $entity): void {}

    /**
     * Triggered before an entity is deleted from the database.
     *
     * @param T $entity
     */
    public function deleting(object $entity): void {}

    /**
     * Triggered after an entity is deleted from the database.
     *
     * @param T $entity
     */
    public function deleted(object $entity): void {}

    /**
     * Triggered before a soft-deleted entity is restored.
     *
     * @param T $entity
     */
    public function restoring(object $entity): void {}

    /**
     * Triggered after a soft-deleted entity is restored.
     *
     * @param T $entity
     */
    public function restored(object $entity): void {}

    /**
     * Triggered before an entity is cloned/replicated.
     *
     * @param T $entity
     */
    public function replicating(object $entity): void {}

    /**
     * Triggered after an entity is hydrated from the database.
     *
     * @param T $entity
     */
    public function hydrated(object $entity): void {}
}
